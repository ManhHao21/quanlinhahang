<?php

namespace App\Livewire;

use Exception;
use App\Models\Order;
use Livewire\Component;

use App\Models\OrderItem;
use Mike42\Escpos\Printer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

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
    // public function printInvoice()
    // {
    //     $order = Order::with('orderItems.menu')->find($this->orderId);

    //     if (!$order) {
    //         $this->dispatch('notify', type: 'error', message: 'Không tìm thấy đơn hàng');
    //         return;
    //     }
    //     // dd($order);
    //     $pdf = Pdf::loadView('print.invoice_pdf', compact('order'));
    //     $pdf->setOption('defaultFont', 'DejaVu Sans');
    //     $pdf->setOption('isRemoteEnabled', true);
    //     $pdf->setPaper([0, 0, 226.77, 800], 'portrait'); // Kích thước phù hợp hóa đơn
    //     $this->sendPrintToNetworkPrinter($pdf->output());
    // }

    // public function sendPrintToNetworkPrinter(string $text)
    // {
    //     $printerIp = '192.168.1.235';
    //     $printerPort = 9100;

    //     $fp = @fsockopen($printerIp, $printerPort, $errno, $errstr, 5);
    //     if (!$fp) {
    //         $this->dispatch('notify', type: 'error', message: "Không kết nối được máy in");
    //         return;
    //     }

    //     fwrite($fp, $text);
    //     fclose($fp);

    //     $this->dispatch('notify', type: 'success', message: 'Đã in bill');
    // }

public function printInvoice()
{
    $order = Order::with('orderItems.menu')->find($this->orderId);

    if (!$order) {
        $this->dispatch('notify', type: 'error', message: 'Không tìm thấy đơn hàng');
        return;
    }

    try {
        $connector = new NetworkPrintConnector("192.168.1.235", 9100);
        $printer = new Printer($connector);

        // Thử set bảng mã CP1258 (Vietnamese)
        try {
            $printer->selectCharacterTable(18); // CP1258
            $this->printerEncoding = 'CP1258';
        } catch (\Exception $e) {
            $this->printerEncoding = 'ASCII'; // fallback không dấu
        }

        // Font A
        $printer->setFont(Printer::FONT_A);

        // Header - Căn giữa
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($this->convertToPrinterEncoding("iPOS.vn") . "\n\n");
        $printer->text($this->convertToPrinterEncoding("PHIẾU TẠM TÍNH") . "\n");
        $printer->text("Số HĐ: #" . $order->id . "\n\n");

        // Thông tin hóa đơn - Căn trái
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Mã HĐ: #" . $order->code . "\n");
        $printer->text("TN: " . $this->convertToPrinterEncoding($order->customer_name) . "\n");
        $printer->text("Ngày: " . $order->created_at->format('d/m/Y') . "\n");
        $printer->text("Giờ vào: " . $order->created_at->format('H.i') . "\n");
        $printer->text("Giờ ra: " . now()->format('H.i') . "\n\n");

        // Bảng sản phẩm
        $printer->text("--------------------------------\n");
        $printer->text($this->formatTableRow(["STT", "Tên món", "SL", "Đơn giá", "Thành tiền"]));
        $printer->text("--------------------------------\n");

        foreach ($order->orderItems as $index => $item) {
            $row = [
                $index + 1,
                $item->menu->name,
                $item->quantity,
                number_format($item->price, 0, ',', '.'),
                number_format($item->price * $item->quantity, 0, ',', '.')
            ];
            $printer->text($this->formatTableRow($row));
        }

        // Tổng tiền
        $printer->text("--------------------------------\n");
        $printer->text($this->formatTableRow(["", "", "Thành tiền:", "", number_format($order->total, 0, ',', '.')]));
        $printer->text($this->formatTableRow(["", "", "Tổng tiền:", "", number_format($order->total, 0, ',', '.')]));

        // Footer
        $printer->text("\n--------------------------------\n");
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text($this->convertToPrinterEncoding("MBBank") . "\n");
        $printer->text($this->convertToPrinterEncoding("TRAN MAI THI") . "\n");
        $printer->text("0975410133\n\n");
        $printer->text($this->convertToPrinterEncoding("Cảm ơn quý khách") . "\n");
        $printer->text("Powered by iPOS.vn\n");

        $printer->cut();
        $printer->close();

        $this->dispatch('notify', type: 'success', message: 'Đã in bill thành công');

    } catch (Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Lỗi khi in: ' . $e->getMessage());
    }
}

private $printerEncoding = 'ASCII'; // Mặc định fallback

private function convertToPrinterEncoding($text)
{
    return mb_convert_encoding($text, 'UTF-8', 'auto');
}
private function formatTableRow($columns)
{
    $formats = [
        "%-3s",   // STT
        "%-20s",  // Tên món
        "%3s",    // SL
        "%9s",    // Đơn giá
        "%9s"     // Thành tiền
    ];

    $row = "";
    foreach ($columns as $index => $value) {
        $encoded = $this->convertToPrinterEncoding($value);
        $row .= sprintf($formats[$index], $encoded);
    }

    return $row . "\n";
}

private function removeAccents($string)
{
    $unwanted_array = [
        'Š'=>'S','š'=>'s','Ž'=>'Z','ž'=>'z','À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Æ'=>'A',
        'Ç'=>'C','È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ñ'=>'N','Ò'=>'O',
        'Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O','Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ý'=>'Y','Þ'=>'B',
        'ß'=>'Ss','à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','æ'=>'a','ç'=>'c','è'=>'e','é'=>'e',
        'ê'=>'e','ë'=>'e','ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ð'=>'o','ñ'=>'n','ò'=>'o','ó'=>'o','ô'=>'o',
        'õ'=>'o','ö'=>'o','ø'=>'o','ù'=>'u','ú'=>'u','û'=>'u','ý'=>'y','þ'=>'b','ÿ'=>'y',
        'ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'Ă'=>'A','Ắ'=>'A','Ằ'=>'A','Ẳ'=>'A','Ẵ'=>'A','Ặ'=>'A',
        'đ'=>'d','Đ'=>'D',
        'ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'Ê'=>'E','Ế'=>'E','Ề'=>'E','Ể'=>'E','Ễ'=>'E','Ệ'=>'E',
        'ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'Ô'=>'O','Ố'=>'O','Ồ'=>'O','Ổ'=>'O','Ỗ'=>'O','Ộ'=>'O',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'Ơ'=>'O','Ớ'=>'O','Ờ'=>'O','Ở'=>'O','Ỡ'=>'O','Ợ'=>'O',
        'ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'Ư'=>'U','Ứ'=>'U','Ừ'=>'U','Ử'=>'U','Ữ'=>'U','Ự'=>'U'
    ];
    return strtr($string, $unwanted_array);
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