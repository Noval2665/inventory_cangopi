<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketList extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    static public function generateMarketListNumber(string $year)
    {
        $firstPrefix = 'ORD';
        $secondPrefix = $year;
        $prefix = $firstPrefix . '-' . $secondPrefix;

        $latestMarketListTransaction = MarketList::withTrashed()->whereYear('date', $year)->orderBy('id', 'DESC')->first();

        if ($latestMarketListTransaction) {
            $lastNumber = explode('-', $latestMarketListTransaction->market_list_number)[2];
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newNumber;
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderList()
    {
        return $this->belongsTo(OrderList::class);
    }
}
