<?php

namespace App\Livewire;

use App\Models\Order;
use Livewire\Component;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Models\OrderItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Cart extends Component
{
    public $orderId;
    public $selectedItems = [];
    public $items = [];
    public $total = 0;

    // Additional fields for order information
    public $tableNumber = '';
    public $tableName = '';
    public $staffName = '';
    public $orderDate;
    public $startTime;
    public $endTime;
    protected $listeners = [
        'select-item' => 'handleSelectedItems',
        'order-updated' => 'loadOrder',
    ];

    public function handleSelectedItems($selectedItems)
    {
        $this->selectedItems = $selectedItems;
    }
    public function loadOrder($orderId = null)
    {
        if ($orderId) {
            $this->orderId = $orderId;
        }

        if ($this->orderId) {
            $order = Order::with('orderItems.menu')->find($this->orderId);

            if ($order) {
                // cập nhật dữ liệu như hiện tại
                $this->items = $order->orderItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->menu->name,
                        'price' => $item->price,
                        'qty' => $item->quantity,
                    ];
                })->toArray();

                $this->total = $order->total;
                $this->tableNumber = $order->table_number;
                $this->tableName = $order->table_name;
                $this->staffName = $order->staff_name;
                $this->orderDate = $order->date_ordered;
                $this->startTime = $order->start_time;
                $this->endTime = $order->end_time;
            }
        }
    }


    public function saveOrderInfo()
    {
        if ($this->orderId) {
            $order = Order::find($this->orderId);
            if ($order) {
                $order->update([
                    'table_number' => $this->tableNumber,
                    'table_name' => $this->tableName,
                    'staff_name' => $this->staffName,
                    'date_ordered' => $this->orderDate,
                    'start_time' => $this->startTime,
                    'end_time' => $this->endTime,
                ]);
            }
        }
    }
    public function printInvoice()
    {
        $order = Order::with('orderItems.menu')->find($this->orderId);

        if (!$order) {
            $this->dispatch('notify', type: 'error', message: 'Không tìm thấy đơn hàng');
            return;
        }

        $text = "BILL\n";
        $text .= "----------------------\n";
        foreach ($order->orderItems as $item) {
            $text .= $item->menu->name . " x" . $item->quantity . "\n";
        }
        $text .= "Tổng: " . $order->total . "đ\n\n";

        $this->sendPrintToNetworkPrinter($text);
    }

    public function sendPrintToNetworkPrinter(string $text)
    {
        $printerIp = '192.168.1.100';
        $printerPort = 9100;

        $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        if (!$fp) {
            $this->dispatch('notify', type: 'error', message: "Không kết nối được máy in");
            return;
        }

        fwrite($fp, $text);
        fclose($fp);

        $this->dispatch('notify', type: 'success', message: 'Đã in bill');
    }


    public function decreaseQty($itemId)
    {
        Log::info($this->selectedItems);

        $item = OrderItem::find($itemId);
        if ($item && $item->quantity > 1) {
            $item->decrement('quantity');
            $this->total -= $item->price;
        } elseif ($item && $item->quantity == 1) {
            // Remove item if quantity is 1
            $menuId = $item->menu_id;
            $item->delete();
            $this->total -= $item->price;
            $this->selectedItems = array_values(array_diff($this->selectedItems, [$menuId]));

            Log::info('Selected Items after deletion: ', ['selectedItems' => $this->selectedItems]);
            $this->dispatch('select-item', selectedItems: $this->selectedItems);
        }
    }


    public function increaseQty($itemId)
    {
        Log::info($this->selectedItems);

        $item = OrderItem::find($itemId);
        if ($item) {
            $item->increment('quantity');
            $this->total += $item->price;
        }
    }
    public function render()
    {
        $order = Order::with('orderItems.menu')->find($this->orderId);
        return view('livewire.cart', [
            'orders' => $order ? $order : new Order(),
        ]);
    }
}