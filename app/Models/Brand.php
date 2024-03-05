<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'user_id' => 'integer',
        'is_active' => 'boolean',

    ];

    public function products()
    {
        return $this->hasMany(Product::class, "brand_id", "id");
    }
}
