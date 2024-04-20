<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecipeDetail extends Model
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

    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
