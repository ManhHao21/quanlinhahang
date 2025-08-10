<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'bill_code',
        'table_number',
        'total',
        'date_ordered',
        'start_time',
        'end_time',
        'status'
    ];
    protected $dates = [
        'date_ordered',
        'start_time',
        'end_time',
    ];

    // Thêm các phương thức hoặc quan hệ nếu cần    
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function getFormattedTotalAttribute()
    {
        return number_format($this->total, 2, ',', '.');
    }

    public function getStatusLabelAttribute()
    {
        return match ($this->status) {
            'pending' => 'Chờ xử lý',
            'paid' => 'Đã thanh toán',
            'canceled' => 'Đã hủy',
            default => 'Không xác định',
        };
    }

    public function getStartTimeFormattedAttribute()
    {
        return $this->start_time ? $this->start_time->format('H:i') : null;
    }

    public function getEndTimeFormattedAttribute()
    {
        return $this->end_time ? $this->end_time->format('H:i') : null;
    }

    public function getDateOrderedFormattedAttribute()
    {
        return $this->date_ordered ? $this->date_ordered->format('d/m/Y H:i') : null;
    }

    public function getBillCodeFormattedAttribute()
    {
        return strtoupper($this->bill_code);
    }
}
