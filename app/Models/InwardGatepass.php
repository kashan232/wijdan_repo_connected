<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InwardGatepass extends Model
{
    use HasFactory;
    protected $guarded = [];

    public static function generateInvoiceNo()
    {
        $prefix = 'IGP-';

        $lastInvoice = self::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastInvoice && $lastInvoice->invoice_no) {
            $lastNumber = (int)substr($lastInvoice->invoice_no, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function items()
    {
        return $this->hasMany(InwardGatepassItem::class);
    }
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
