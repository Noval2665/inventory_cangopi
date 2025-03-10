<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'user_id' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, "supplier_id", "id");
    }
}
