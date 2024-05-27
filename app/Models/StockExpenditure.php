<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockExpenditure extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    static public function generateStockExpenditureNumber(string $year)
    {
        $firstPrefix = 'PS';
        $secondPrefix = $year;
        $prefix = $firstPrefix . '-' . $secondPrefix;

        $latestStockExpenditureTransaction = StockExpenditure::withTrashed()->whereYear('date', $year)->orderBy('id', 'DESC')->first();

        if ($latestStockExpenditureTransaction) {
            $lastNumber = explode('-', $latestStockExpenditureTransaction->stock_expenditure_number)[2];
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
        return $this->hasMany(StockExpenditureDetail::class);
    }
}
