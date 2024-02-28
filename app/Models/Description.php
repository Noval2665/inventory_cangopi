<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Description extends Model
{
    use HasFactory, SoftDeletes;
    protected $guarded = [];
    protected $dates = ['deactivated_at'];
    protected $casts = [
        'status' => 'boolean',
        'description_type' => 'string',
    ];

    public function PurchaseOrderDetails(){
        return $this->hasMany(PurchaseOrderDetail::class, "description_id", "id");
    }

    public function PurchaseDetails(){
        return $this->hasMany(PurchaseDetail::class, "description_id", "id");
    }
}
