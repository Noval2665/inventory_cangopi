<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'product_name' => 'string',
        'purchase_price' => 'double',
        'stock' => 'double',
        'measurement' => 'double',
        'sub_category_id' => 'integer',
        'storage_id' => 'integer',
        'brand_id' => 'integer',
        'unit_id' => 'integer',
        'metric_id' => 'integer',
        'supplier_id' => 'integer',
        'is_active' => 'boolean',
        'user_id' => 'integer',
    ];

    static public function generateProductCode(string $year)
    {
        $firstPrefix = 'PRD';
        $secondPrefix = $year;
        $prefix = $firstPrefix . '-' . $secondPrefix;

        $latestProductCode = Product::whereYear('created_at', $year)->orderBy('id', 'DESC')->first();

        if ($latestProductCode) {
            $lastCode = explode('-', $latestProductCode->product_code)[2];
            $newCode = $lastCode + 1;
        } else {
            $newCode = 1;
        }

        $newCode = str_pad($newCode, 4, '0', STR_PAD_LEFT);

        return $prefix . '-' . $newCode;
    }

    public function subCategory()
    {
        return $this->belongsTo(SubCategory::class, "sub_category_id", "id");
    }
    public function storage()
    {
        return $this->belongsTo(Storage::class, "storage_id", "id");
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }
    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id', "id");
    }
    public function metric()
    {
        return $this->belongsTo(Metric::class, "metric_id", "id");
    }
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, "supplier_id", "id");
    }
    public function orderLists()
    {
        return $this->hasMany(OrderList::class, "product_id", "id");
    }
}
