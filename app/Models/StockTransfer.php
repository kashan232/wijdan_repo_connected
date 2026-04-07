<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockTransfer extends Model
{
    use HasFactory;

    protected $guarded = [];


    protected $casts = [
        'product_id' => 'array',
        'quantity' => 'array',
    ];

    public function fromWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'from_warehouse_id');
    }

    public function toWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'to_warehouse_id');
    }

    public function getProductsAttribute()
    {
        // Normalize product_id into an array
        $ids = $this->product_id;

        if (is_string($ids)) {
            $decoded = json_decode($ids, true);
            $ids = is_array($decoded) ? $decoded : [$ids];
        } elseif (is_int($ids)) {
            $ids = [$ids];
        } elseif (!is_array($ids)) {
            $ids = [];
        }

        return Product::whereIn('id', $ids)->get();
    }
}
