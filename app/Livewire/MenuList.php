<?php

namespace App\Livewire;

use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class MenuList extends Component
{
    public $search = '';
    public $orderId;
    public $selectedItems = [];
    public $currentOrderId = null;
    protected $listeners = [
        'select-item' => 'handleSelectedItems',
    ];
    public function handleSelectedItems($selectedItems)
    {
        $this->selectedItems = $selectedItems;
    }
    public function mount($orderId = null): void
    {
        $this->orderId = $orderId;
        // $this->menuItems = Menu::all();
    }

    public function toggleItem($id)
    {
        // 1. Update the selectedItems array
        if (in_array($id, $this->selectedItems)) {
            $this->selectedItems = array_diff($this->selectedItems, [$id]);
        } else {
            $this->selectedItems[] = $id;
        }
        Log::info('Selected Items: ', ['selectedItems' => $this->selectedItems]);
        // Make sure selected IDs are unique and re-indexed
        $this->dispatch('select-item', selectedItems: $this->selectedItems);


        // 2. Handle order deletion if no items are selected
        if (empty($this->selectedItems)) {
            if ($this->currentOrderId) {
                Order::destroy($this->currentOrderId);
                $this->currentOrderId = null;
            }
            session()->flash('message', 'All items removed, order cancelled.');
            return;
        }

        // 3. Use a transaction to ensure data consistency
        DB::transaction(function () use ($id) {
            // Get or create the Order
            $order = Order::find($this->currentOrderId);
            if (!$order) {
                $order = new Order();
                $randomString = Str::random(7);
                $order->bill_code = strtoupper($randomString);
                $order->table_number = '';
                $order->date_ordered = now();
                $order->start_time = now();
                $order->end_time = now()->addHours(1);
                $order->status = 'pending';
                $order->save();
                $this->currentOrderId = $order->id; // Lưu ID của Order mới
                session()->flash('message', 'Order created with Bill Code: ' . $order->bill_code);
            }
            $menu = Menu::findOrFail($id);
            if (!$menu) {
                session()->flash('error', 'Menu item not found.');
                return;
            }
            $OrderItem = OrderItem::where('order_id', $order->id)
                ->where('menu_id', $menu->id)
                ->first();

            if ($OrderItem) {
                $OrderItem->quantity += 1;
                $OrderItem->save();
            } else {
                $OrderItem = new OrderItem();
                $OrderItem->order_id = $order->id;
                $OrderItem->menu_id = $menu->id;
                $OrderItem->quantity = 1;
                $OrderItem->price = $menu->price;
                $OrderItem->save();
            }

            $totalAmount = $order->orderItems()->sum(DB::raw('quantity * price'));
            // 4. Sync Order_items and recalculate the total
            // $totalAmount = 0;

            // $currentSelectedIds = collect($this->selectedItems);

            // // Remove unselected items
            //  $orderItem  = OrderItem::where('order_id', $order->id)
            //     ->whereNotIn('menu_id', $currentSelectedIds)
            //     ->delete();
            // dd($orderItem);
            // dd($order->orderItems);
            // // Add or update selected items
            // foreach ($currentSelectedIds as $menuId) {
            //     $menu = Menu::find($menuId);
            //     if ($menu) {
            //         $orderItem = OrderItem::firstOrNew([
            //             'order_id' => $order->id,
            //             'menu_id' => $menu->id
            //         ]);

            //         if ($orderItem->exists) {
            //             $orderItem->quantity = 1;
            //         } else {
            //             $orderItem->quantity = 1;
            //         }

            //         $orderItem->price = $menu->price;
            //         $orderItem->save();

            //         $totalAmount += $orderItem->quantity * $orderItem->price;
            //     }
            // }

            // Update the order's total
            $order->total = $totalAmount;
            $order->save();

            // Emit the event to inform other components
            $this->dispatch('order-updated', orderId: $order->id);
        });
    }
    public function removeItem($itemId)
    {
        // Bỏ khỏi mảng selectedItems
        $this->selectedItems = array_filter($this->selectedItems, fn($id) => $id != $itemId);

        // Nếu bạn có lưu trong DB thì xóa luôn
        // OrderItem::where('menu_item_id', $itemId)->delete();
    }

    public function render($orderId = null)
    {

        $menuItems = Menu::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy('name')
            ->get();

        if ($this->orderId) {
            $this->currentOrderId = $this->orderId;
            $this->selectedItems = OrderItem::where('order_id', $this->currentOrderId)
                ->pluck('menu_id')
                ->toArray();
        }
        return view('livewire.menu-list', compact('menuItems'));
    }
}