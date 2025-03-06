<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ParStock extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    static public function generateParStockNumber(string $year)
    {
        $firstPrefix = 'PAR';
        $secondPrefix = $year;
        $prefix = $firstPrefix . '-' . $secondPrefix;

        $latestParStockTransaction = ParStock::withTrashed()->whereYear('date', $year)->orderBy('id', 'DESC')->first();

        if ($latestParStockTransaction) {
            $lastNumber = explode('-', $latestParStockTransaction->par_stock_number)[2];
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        $newNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newNumber;
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function details()
    {
        return $this->hasMany(ParStockDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
