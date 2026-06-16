<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InwardReturnItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function inwardReturn()
    {
        return $this->belongsTo(InwardReturn::class, 'inward_return_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
