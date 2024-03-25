<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

<<<<<<< HEAD
class purchase_report extends Model
=======
class ParStockProduct extends Model
>>>>>>> 6b61013e59b4772727db4b17f5a88d0c76d8980d
{
    use HasFactory;
    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
