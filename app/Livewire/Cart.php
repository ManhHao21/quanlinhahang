<?php

namespace App\Livewire;

use Exception;
use App\Models\Order;
use Livewire\Component;
use Normalizer;
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

        // Hàm bỏ dấu tiếng Việt và chỉ giữ ký tự ASCII
        $removeAccents = function (?string $str): string {
            if ($str === null || $str === '') {
                return '';
            }
            $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
            return preg_replace('/[^ -~]/', '', $str); // Xóa ký tự ngoài ASCII
        };

        // Hàm format hàng bảng (căn lề và tạo khoảng cách)
        $formatTableRow = function (array $columns, array $widths = [4, 14, 4, 8, 10]): string {
            $row = '';
            foreach ($columns as $i => $col) {
                $row .= str_pad($col, $widths[$i] ?? 10);
            }
            return $row . "\n";
        };

        $conn = $printer->getPrintConnector();

        // Font A
        $conn->write(chr(27) . "M" . chr(0));

        // Center alignment
        $conn->write(chr(27) . "a" . chr(1));
        $conn->write($this->removeAccents("iPOS.vn") . "\n\n");
        $conn->write($this->removeAccents("PHIEU TAM TINH") . "\n");
        $conn->write($this->removeAccents("So HD: #") . $order->id . "\n\n");

        // Left alignment
        $conn->write(chr(27) . "a" . chr(0));
        $conn->write($this->removeAccents("Ma HD: #") . $order->code . "\n");
        $conn->write($this->removeAccents("TN: ") . $this->removeAccents($order->customer_name) . "\n");
        $conn->write($this->removeAccents("Ngay: ") . $order->created_at->format('d/m/Y') . "\n");
        $conn->write($this->removeAccents("Gio vao: ") . $order->created_at->format('H.i') . "\n");
        $conn->write($this->removeAccents("Gio ra: ") . now()->format('H.i') . "\n\n");

        // Tiêu đề bảng
        $conn->write($formatTableRow([
            'STT', 'Ten mon', 'SL', 'Don gia', 'Thanh tien'
        ]));
        $conn->write(str_repeat('-', 42) . "\n");

        // Dữ liệu món ăn
        foreach ($order->orderItems as $index => $item) {
            $conn->write($formatTableRow([
                (string) ($index + 1),
                $this->removeAccents($item->menu->name),
                (string) $item->quantity,
                number_format($item->price, 0, ',', '.'),
                number_format($item->price * $item->quantity, 0, ',', '.')
            ]));
        }

        $conn->write("------------------------------------------\n");
        $conn->write($formatTableRow([
            "", "", "Thanh tien:", "", number_format($order->total, 0, ',', '.')
        ]));

        // Footer
        $conn->write("\n------------------------------------------\n");
        $conn->write(chr(27) . "a" . chr(1));
        $conn->write($this->removeAccents("MBBank") . "\n");
        $conn->write($this->removeAccents("TRAN MAI THI") . "\n");
        $conn->write("0975410133\n\n");
        $conn->write($this->removeAccents("Cam on quy khach") . "\n");
        $conn->write("Powered by iPOS.vn\n");

        // Cut giấy
        $conn->write(chr(29) . "V" . chr(1));

        $this->dispatch('notify', type: 'success', message: 'Đã in bill thành công');
    } catch (\Exception $e) {
        $this->dispatch('notify', type: 'error', message: 'Lỗi khi in: ' . $e->getMessage());
    } finally {
        if (isset($printer)) {
            $printer->close();
        }
    }
}

    function formatTableRow($cols)
    {
        // Thêm 1 phần tử rỗng ở vị trí khoảng trắng giữa đơn giá và thành tiền
        $widths = [4, 15, 4, 8, 2, 9]; // Tổng = 42
        $row = '';

        foreach ($cols as $i => $text) {
            $text = mb_substr($text, 0, $widths[$i], 'UTF-8');
            if (in_array($i, [0, 2])) {
                $row .= str_pad($text, $widths[$i], ' ', STR_PAD_BOTH);
            } elseif ($i >= 3) {
                $row .= str_pad($text, $widths[$i], ' ', STR_PAD_LEFT);
            } else {
                $row .= str_pad($text, $widths[$i], ' ', STR_PAD_RIGHT);
            }
        }
        return $row . "\n";
    }
function removeAccents($str) {
    // Chuẩn hóa Unicode (tách ký tự + dấu)
    $str = Normalizer::normalize($str, Normalizer::FORM_D);

    // Xóa các dấu (ký tự tổ hợp)
    $str = preg_replace('/\p{M}/u', '', $str);

    return $str;
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