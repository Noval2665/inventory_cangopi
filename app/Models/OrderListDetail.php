<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderListDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'double',
        'discount_amount' => 'double',
        'total_price' => 'double',
    ];

    public function orderList()
    {
        return $this->belongsTo(OrderList::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function description()
    {
        return $this->belongsTo(Description::class);
    }
}
