<?php

namespace App\Livewire;

use Livewire\Component;

class CreateProduct extends Component
{
    public $name;
    public $price;
    public $description;    
    public function render()
    {
        return view('livewire.create-product');
    }
}
