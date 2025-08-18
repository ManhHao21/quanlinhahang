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
