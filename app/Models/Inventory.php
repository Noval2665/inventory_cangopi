<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Inventory extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'inventory_type' => 'string',
        'user_id' => 'integer',
    ];

    // public function products()
    // {
    //     return $this->hasMany(Product::class, "inventory_id", "id");
    // }

    public function storages()
    {
        return $this->hasMany(Storage::class, "inventory_id", "id");
    }
}
