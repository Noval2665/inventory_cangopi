<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderList extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'quantity' => 'double',
        'total_price' => 'double',
    ];

    static public function generateOrderListNumber(string $year)
    {
        $firstPrefix = 'ORD';
        $secondPrefix = $year;
        $prefix = $firstPrefix . '-' . $secondPrefix;

        $latestOrderListTransaction = OrderList::withTrashed()->whereYear('date', $year)->orderBy('id', 'DESC')->first();

        if ($latestOrderListTransaction) {
            $lastNumber = explode('-', $latestOrderListTransaction->order_list_number)[2];
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newNumber;
    }

    public function details()
    {
        return $this->hasMany(OrderListDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
