<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $dates = ['deactivated_at'];

    protected $casts = [
        'is_active' => 'boolean',
        'product_name' => 'string',
    ];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', "id");
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', "id");
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, 'sub_category_id', "id");
    }

    public function orderLists()
    {
        return $this->hasMany(OrderList::class, "product_id", "id");
    }
}
