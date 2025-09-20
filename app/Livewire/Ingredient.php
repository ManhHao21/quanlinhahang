<?php

namespace App\Livewire;
use Livewire\Component;
use App\Models\Ingredient as IngredientModel;


class Ingredient extends Component
{
    public $name;
    public $unit;
    public $cost_price;

    protected $rules = [
        'name' => 'required|string|max:100',
        'unit' => 'required|string|max:20',
    ];

    public function save()
    {
        $this->validate();

            $ingredient = IngredientModel::create([
            'name' => $this->name,
            'unit' => $this->unit,
        ]);

        session()->flash('success', 'Nguyên liệu đã được thêm thành công!');
        $this->reset(); // reset form sau khi lưu
        $ingredientAll = IngredientModel::all();
        $this->dispatch('ingredient-added', $ingredient->id);

    }

    public function render()
    {
        return view('livewire.ingredient');
    }
}
