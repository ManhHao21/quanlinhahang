<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Order;
use Livewire\Component;
use Mike42\Escpos\Printer;
use Livewire\WithPagination;
use Mike42\Escpos\EscposImage;
use Mike42\Escpos\PrintConnectors\NetworkPrintConnector;

class OrderHistory extends Component
{
    use WithPagination;

    public $search = '';
    public $date;
    public $totalDaily = 0;
    public $totalMonthly = 0;

    protected $updatesQueryString = ['search', 'date'];

    public function mount()
    {
        $this->date = now()->format('Y-m-d'); // Mặc định hôm nay
        $this->calculateRevenue();
    }

    // Nút "Lọc" bấm mới chạy
    public function applyFilters()
    {
        $this->calculateRevenue();
        $this->resetPage();
    }

    private function calculateRevenue()
    {
        // Doanh thu theo ngày
        $this->totalDaily = Order::whereDate('date_ordered', $this->date)
            ->sum('total');

        // Doanh thu theo tháng
        $this->totalMonthly = Order::whereMonth('date_ordered', Carbon::parse($this->date)->month)
            ->whereYear('date_ordered', Carbon::parse($this->date)->year)
            ->sum('total');
    }

    public function viewOrderDetails($id)
    {
        return redirect()->route('orders.show', $id);
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
                'STT',
                'Ten mon',
                '',
                'SL',
                'Don gia',
                '',
                'Thanh tien'
            ]));
            $conn->write(str_repeat('-', 42) . "\n");
            $totalAmount = 0;
            // Dữ liệu món ăn
            foreach ($order->orderItems as $index => $item) {
                $conn->write($this->formatTableRow([
                    (string) ($index + 1),
                    $this->removeAccents($item->menu->name),
                    "", // cột trống làm khoảng cách
                    (string) $item->quantity,
                    number_format($item->price, 0, ',', '.'),
                    "",
                    number_format($item->price * $item->quantity, 0, ',', '.')
                ]));

                $totalAmount += $item->price * $item->quantity;
            }

            $conn->write("------------------------------------------\n");
            $conn->write($formatTableRow([
                "",
                "",
                "Thanh tien:",
                "",
                number_format($totalAmount, 0, ',', '.')
            ]));

            // Footer
            $conn->write("\n------------------------------------------\n");
            $conn->write(chr(27) . "a" . chr(1));
            $conn->write($this->removeAccents("MBBank") . "\n");
            $conn->write($this->removeAccents("TRAN MAI THI") . "\n");
            $qrPath = public_path('images/qr.png');
            if (file_exists($qrPath)) {
                $qrImg = EscposImage::load($qrPath, false);
                $printer->bitImage($qrImg);
            } else {
                $conn->text("Không tìm thấy QR code\n");
            }



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
        $widths = [4, 15, 2, 6, 8, 2, 9]; // thêm 2 ký tự khoảng trắng
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

    public function render()
    {
        $orders = Order::with('orderItems.menu')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('bill_code', 'like', "%{$this->search}%")
                        ->orWhere('table_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->date, function ($query) {
                $query->whereDate('date_ordered', $this->date);
            })
            ->orderBy('date_ordered', 'desc')
            ->paginate(10)->withQueryString();

        return view('livewire.order-history', [
            'orders' => $orders,
        ]);
    }

    public function clearFilters()
    {

    }
}
