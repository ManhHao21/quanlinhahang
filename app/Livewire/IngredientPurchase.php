<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Ingredient;
use App\Models\IngredientPurchase as IngredientPurchaseModel; // alias để tránh trùng tên

class IngredientPurchase extends Component
{
    public $ingredient_id;
    public $price;
    public $purchase_date;
    public $ingredients;

    protected $listeners = ['ingredient-added' => 'refreshIngredients']; // 👈 thêm

    protected $rules = [
        'ingredient_id' => 'required|exists:ingredients,id',
        'price' => 'required|numeric|min:0',
        'purchase_date' => 'required|date',
    ];

    public function mount()
    {
        $this->ingredients = Ingredient::all();
        $this->purchase_date = now()->toDateString();
    }

    public function refreshIngredients()
    {
        $this->ingredients = Ingredient::all(); // reload lại danh sách nguyên liệu
    }

    public function save()
    {
        $this->validate();

        IngredientPurchaseModel::create([
            'ingredient_id' => $this->ingredient_id,
            'price' => $this->price,
            'purchase_date' => $this->purchase_date,
        ]);

        session()->flash('success', 'Đã lưu phiếu mua nguyên liệu thành công!');
        $this->reset(['ingredient_id', 'price']);
        $this->purchase_date = now()->toDateString();
    }

    public function render()
    {
        return view('livewire.ingredient-purchase');
    }
}
