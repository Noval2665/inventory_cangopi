<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseReturn extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    static public function generatePurchaseReturnNumber(string $month, string $year)
    {
        $prefix = 'R-ORD-' . $year . '-' . $month . '-';

        $transactions = PurchaseReturn::whereYear('date', $year)->get();

        $invoiceNumbers = [];
        foreach ($transactions as $transaction) {
            $number = explode('-', $transaction->purchase_return_number)[4];
            $invoiceNumbers[] = (int)$number;
        }

        sort($invoiceNumbers);

        $newNumber = 1;
        foreach ($invoiceNumbers as $number) {
            if ($newNumber < $number) {
                break;
            }
            $newNumber++;
        }

        $newNumber = str_pad($newNumber, 4, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function orderList()
    {
        return $this->belongsTo(OrderList::class);
    }

    public function details()
    {
        return $this->hasMany(PurchaseReturnDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
