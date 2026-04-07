<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    protected $guarded = [];

    // ✅ Each return belongs to a vendor
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    // ✅ Each return belongs to a warehouse
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    // ✅ Each return has many items
    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id', 'id');
    }

    public function purchase()
    {
        return $this->belongsTo(\App\Models\Purchase::class, 'purchase_id');
    }
}
