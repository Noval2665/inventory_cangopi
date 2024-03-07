<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Storage extends Model
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
        return $this->hasMany(Product::class, "storage_id", "id");
    }

    public function parStocks(){
        return $this->hasMany(ParStock::class, "storage_id", "id");
    }
}
