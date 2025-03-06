<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Opname extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    static function generateOpnameNumber(string $month, string $year)
    {
        $prefix = 'OP' . $year . '-' . $month . '-';

        $transactions = Opname::whereYear('date', $year)->get();

        $invoiceNumbers = [];
        foreach ($transactions as $transaction) {
            $number = explode('-', $transaction->opname_number)[2];
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

    public function details()
    {
        return $this->hasMany(OpnameDetail::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
