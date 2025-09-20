<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IngredientPurchase extends Model
{
    protected $fillable = [
        'ingredient_id',
        'price',
        'purchase_date',
    ];

    public function ingredient()
    {
        return $this->belongsTo(Ingredient::class);
    }
}
