<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public static function generateInvoiceNo()
    {
        $prefix = 'PUR-';

        $lastInvoice = self::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int)substr($lastInvoice->invoice_no, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }


    protected $casts = [
        'purchase_date' => 'date',
        'subtotal'      => 'decimal:2',
        'discount'      => 'decimal:2',
        'extra_cost'    => 'decimal:2',
        'net_amount'    => 'decimal:2',
        'paid_amount'   => 'decimal:2',
        'due_amount'    => 'decimal:2',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }
    public function items()
    {
        return $this->hasMany(PurchaseItem::class);
    }

    public function return()
    {
        return $this->hasOne(PurchaseReturn::class, 'purchase_id');
    }
}
