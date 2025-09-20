<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\IngredientPurchase;
use Carbon\Carbon;

class IngredientPurchaseHistory extends Component
{
    public $purchases = [];
    public $searchDate;   // ngày cần tìm kiếm (YYYY-MM-DD)
    public $totalDay = 0; // tổng trong ngày
    public $totalMonth = 0; // tổng trong tháng

    public function mount()
    {
        $this->searchDate = now()->toDateString(); // mặc định hôm nay
        $this->loadData();
    }

    public function updatedSearchDate()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $date = Carbon::parse($this->searchDate)->toDateString();

        // lấy danh sách mua nguyên liệu đúng ngày search
        $this->purchases = IngredientPurchase::with('ingredient')
            ->whereDate('purchase_date', $date)
            ->orderBy('purchase_date', 'desc')
            ->get();

        // tổng theo ngày
        $this->totalDay = $this->purchases->sum('price');

        // tổng theo tháng
        $this->totalMonth = IngredientPurchase::whereYear('purchase_date', Carbon::parse($date)->year)
            ->whereMonth('purchase_date', Carbon::parse($date)->month)
            ->sum('price');
    }

    public function delete($id)
    {
        $purchase = IngredientPurchase::find($id);
        if ($purchase) {
            $purchase->delete();
        }

        // reload lại data sau khi xóa
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.ingredient-purchase-history');
    }
}
