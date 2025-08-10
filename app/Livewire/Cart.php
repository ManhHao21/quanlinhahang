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
    public function sendPrintToNetworkPrinter(string $text)
    {
        $printerIp = '192.168.1.100';  // Thay IP máy in của bạn
        $printerPort = 9100;           // Port in thường là 9100

        $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
        if (!$fp) {
            session()->flash('error', "Không kết nối được máy in: $errstr ($errno)");
            return false;
        }

        fwrite($fp, $text);
        fclose($fp);

        session()->flash('message', 'Đã gửi lệnh in tới máy in.');
        return true;
    }

    public function printInvoice()
    {
        $order = Order::with([
            'orderItems.menu' => function ($query) {
                $query->select('id', 'name', 'price', 'description');
            }
        ])->find($this->orderId);

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        // Xử lý encoding đặc biệt cho tiếng Việt
        $orderArray = $this->fixVietnameseEncoding($order->toArray());

        try {
            $html = view('print.invoice_pdf', [
                'order' => $orderArray,
                'encoding' => 'UTF-8'
            ])->render();

            $pdf = PDF::loadHTML($html)
                ->setPaper([0, 0, 164, 500], 'portrait')
                ->setOptions([
                    'defaultFont' => 'DejaVu Sans',
                    'isRemoteEnabled' => true,
                    'isHtml5ParserEnabled' => true
                ]);

            return $pdf->stream('invoice.pdf');

        } catch (\Exception $e) {
            \Log::error('PDF Error', [
                'message' => $e->getMessage(),
                'order' => $orderArray,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'PDF generation failed'], 500);
        }
    }

    protected function fixVietnameseEncoding($data)
    {
        $data = json_decode(json_encode($data), true);
        array_walk_recursive($data, function (&$value) {
            if (is_string($value)) {
                if (preg_match('/[ÃÂÈÉÊÌÍÒÓÔÕÙÚÝàáâãèéêìíòóôõùúýÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝ]/u', $value)) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }
                $value = iconv('UTF-8', 'UTF-8//IGNORE', $value);
                $value = preg_replace('/[^\x09\x0A\x0D\x20-\x7E\xC2-\xF4][\x80-\xBF]*/', '', $value);
            }
        });
        return $data;
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