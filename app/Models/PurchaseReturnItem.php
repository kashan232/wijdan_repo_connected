<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturnItem extends Model
{
    protected $guarded = [];

    public function purchaseReturn()
    {
        return $this->belongsTo(PurchaseReturn::class, 'purchase_return_id', 'id');
    }

    // ✅ Belongs to product
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
