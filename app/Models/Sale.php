<?php

// app/Models/Sale.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $guarded = [];
    protected $casts = [
        'total_items' => 'float',
    ];

    public static function generateInvoiceNo()
    {
        $prefix = 'SAL-';

        $lastInvoice = self::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int)substr($lastInvoice->invoice_no, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function customer_relation()
    {
        return $this->belongsTo(Customer::class, 'customer', 'id');
    }

    public function product_relation()
    {
        return $this->belongsTo(Product::class, 'product', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
