<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class purchase_report extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
