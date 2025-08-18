<?php

namespace App\Livewire;

use Exception;
use Normalizer;
use App\Models\Menu;
use App\Models\Order;
use Livewire\Component;
use App\Models\OrderItem;
use Mike42\Escpos\Printer;
use Mike42\Escpos\EscposImage;
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
    public $tableNumber;
    public $tableName;
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
    public function printInvoice($id)
    {
        $this->orderId = $id;
        if (!$this->orderId) {
            $this->dispatch('notify', type: 'error', message: 'Vui lòng chọn đơn hàng để in');
            return;
        }

        $order = Order::with('orderItems.menu')->find($this->orderId);
        if (!$order) {
            $this->dispatch('notify', type: 'error', message: 'Không tìm thấy đơn hàng');
            return;
        }

        try {
            $connector = new NetworkPrintConnector("192.168.1.235", 9100);
            $printer = new Printer($connector);

            // Bỏ dấu tiếng Việt
            $removeAccents = function (?string $str): string {
                if ($str === null || $str === '') {
                    return '';
                }
                $str = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $str);
                return preg_replace('/[^ -~]/', '', $str);
            };

            // Format hàng bảng (cố định cột)
            $formatTableRow = function (array $cols): string {
                $widths = [4, 15, 6, 10, 12]; // STT | Tên món | SL | Đơn giá | Thành tiền
                $row = '';
                foreach ($cols as $i => $text) {
                    $text = mb_substr($text, 0, $widths[$i], 'UTF-8');
                    if ($i == 0) { // STT
                        $row .= str_pad($text, $widths[$i], ' ', STR_PAD_RIGHT);
                    } elseif ($i == 1) { // Tên món
                        $row .= str_pad($text, $widths[$i], ' ', STR_PAD_RIGHT);
                    } else { // số liệu thì canh phải
                        $row .= str_pad($text, $widths[$i], ' ', STR_PAD_LEFT);
                    }
                }
                return $row . "\n";
            };

            $conn = $printer->getPrintConnector();

            // Font A
            $conn->write(chr(27) . "M" . chr(0));

            // Center title
            $conn->write(chr(27) . "a" . chr(1));
            $conn->write($removeAccents("PHIEU TAM TINH") . "\n");
            $conn->write($removeAccents("So HD: #") . $order->id . "\n\n");

            // Left info
            $conn->write(chr(27) . "a" . chr(0));
            $conn->write($removeAccents("Ma HD: #") . $order->bill_code . "\n");
            $conn->write($removeAccents("Số bàn: ") . $removeAccents($order->table_number ?? "N/a") . "\n");
            $conn->write($removeAccents("Tên bàn: ") . $removeAccents($order->table_name ?? "N/a") . "\n");
            $conn->write($removeAccents("Ngay: ") . $order->created_at->format('d/m/Y') . "\n");
            $conn->write($removeAccents("Gio vao: ") . $order->created_at->format('H.i') . "\n");
            $conn->write($removeAccents("Gio ra: ") . now()->format('H.i') . "\n\n");

            // Header table
            $conn->write($formatTableRow([
                'STT',
                'Ten mon',
                'SL',
                'Don gia',
                'Thanh tien'
            ]));
            $conn->write(str_repeat('-', 47) . "\n");

            $totalAmount = 0;
            foreach ($order->orderItems as $index => $item) {
                $conn->write($formatTableRow([
                    (string) ($index + 1),
                    $removeAccents($item->menu->name),
                    (string) $item->quantity,
                    number_format($item->price, 0, ',', '.'),
                    number_format($item->price * $item->quantity, 0, ',', '.')
                ]));
                $totalAmount += $item->price * $item->quantity;
            }

            $conn->write(str_repeat('-', 47) . "\n");
            $conn->write($formatTableRow([
                '',
                'Thanh tien:',
                '',
                '',
                number_format($totalAmount, 0, ',', '.')
            ]));

            // Footer
            $conn->write("\n------------------------------------------\n");
            $conn->write(chr(27) . "a" . chr(1));
            $conn->write($removeAccents("MBBank") . "\n");
            $conn->write($removeAccents("TRAN MAI THI") . "\n");
            $qrPath = public_path('images/qr.png');
            if (file_exists($qrPath)) {
                $qrImg = EscposImage::load($qrPath, false);
                $printer->bitImage($qrImg);
            } else {
                $conn->text("Không tìm thấy QR code\n");
            }
            $conn->write($removeAccents("Cam on quy khach") . "\n");
            $conn->write("Powered by iPOS.vn\n");

            // Cut
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
        $widths = [4, 15, 1, 6, 8, 1, 9]; // thêm 2 ký tự khoảng trắng
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
    function removeAccents($str)
    {
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
    public function tempOrder($id)
    {
        if ($id == null) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Vui lòng chọn sản phẩm'
            ]);
            return;
        }
        // Validate dữ liệu đầu vào
        $this->validate([
            'tableNumber' => 'required|integer',
        ], [
            'tableNumber.required' => 'Số bàn là bắt buộc',
            'tableNumber.integer' => 'Số bàn phải là số',
        ]);

        // Lấy order kèm món ăn
        $order = Order::with('orderItems')->find($id);

        if (!$order) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Không tìm thấy đơn hàng!'
            ]);
            return;
        }

        // Kiểm tra đơn hàng có món chưa
        if ($order->orderItems->count() === 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Đơn hàng phải có ít nhất một món ăn!'
            ]);
            return;
        }

        // Cập nhật trạng thái + 2 cột khác
        $order->update([
            'status' => Order::STATUS_TEMPOLARY,
            'table_number' => $this->tableNumber, // lấy từ form/livewire
            'bill_code' => $this->billCode ?? $order->bill_code, // nếu có input bill_code
        ]);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Đã lưu tạm đơn hàng thành công!'
        ]);

        $this->dispatch('reloadPage');
    }

    public function paymentSuccess($orderId = null)
    {
        $this->validate([
            'tableNumber' => 'required|integer',
            'tableName' => 'required|string|max:255',
        ], [
            'tableNumber.required' => 'Số bàn là bắt buộc',
            'tableNumber.integer' => 'Số bàn phải là số',
            'tableName.required' => 'Tên bàn là bắt buộc',
        ]);

        DB::transaction(function () use ($orderId) {
            // 1. Tạo hoặc cập nhật Order
            $order = Order::find($this->orderId);
            if (!$order) {
                $order = new Order();
                $order->bill_code = Order::generateBillCode();
            }

            $order->table_number = $this->tableNumber;
            $order->table_name = $this->tableName;
            $order->date_ordered = now();
            $order->start_time = now();
            $order->end_time = now()->addHours(1);
            $order->status = Order::STATUS_SUCCESS;
            $order->save();

            // $this->dispatch('order-updated', orderId: $order->id);
            session()->flash('message', 'Đơn hàng đã được thanh toán thành công với mã hóa đơn: ' . $order->bill_code);
        });
    }
    public function render()
    {
        $order = Order::with('orderItems.menu')->find($this->orderId);
        if ($order) {
            $this->tableName = $order->table_name;
            $this->tableNumber = $order->table_number;
            $this->staffName = $order->staff_name;
            $this->orderDate = $order->date_ordered;
            $this->startTime = $order->start_time;
            $this->endTime = $order->end_time;
        }
        return view('livewire.cart', [
            'orders' => $order ? $order : new Order(),
        ]);
    }
}