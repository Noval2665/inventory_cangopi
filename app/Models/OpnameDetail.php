<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpnameDetail extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public function opname()
    {
        return $this->belongsTo(Opname::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
