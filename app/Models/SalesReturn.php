<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'customer', 'reference', 'product', 'product_code', 'brand',
        'unit', 'per_price', 'per_discount', 'qty', 'per_total',
        'total_amount_Words', 'total_bill_amount', 'total_extradiscount',
        'total_net', 'cash', 'card', 'change', 'color', 'total_items',
        'return_note',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
