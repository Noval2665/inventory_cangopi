<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'user_id' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function finishedProduct()
    {
        return $this->belongsTo(Product::class, 'finished_product_id', 'id');
    }

    public function details()
    {
        return $this->hasMany(RecipeDetail::class);
    }

    //     public function parStock()
    //     {
    //         return $this->belongsTo(ParStock::class);
    //     }
}
