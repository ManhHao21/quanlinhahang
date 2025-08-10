<?php

namespace App\Models;

use App\Models\Menu;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;
    protected $table = 'order_items'; // Đặt tên bảng nếu khác mặc định
    protected $fillable = [
        'order_id',
        'menu_id',
        'quantity',
        'price'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function getFormattedPriceAttribute()
    {
        return number_format($this->price, 2, ',', '.');
    }

    public function getTotalPriceAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function getFormattedTotalPriceAttribute()
    {
        return number_format($this->getTotalPriceAttribute(), 2, ',', '.');
    }

    public function getMenuNameAttribute()
    {
        return $this->menu ? $this->menu->name : 'Món ăn không xác định';
    }
}
