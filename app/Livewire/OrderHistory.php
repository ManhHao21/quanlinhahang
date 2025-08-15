<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Order;
use Carbon\Carbon;

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
            ->paginate(10);

        return view('livewire.order-history', [
            'orders' => $orders,
        ]);
    }
}
