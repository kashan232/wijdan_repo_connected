<?php

namespace App\Models;

use App\Traits\BelongsToAccountingPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InwardReturn extends Model
{
    use HasFactory, BelongsToAccountingPeriod;

    protected $guarded = [];

    public static function generateInvoiceNo()
    {
        $prefix = 'IGR-';

        $lastInvoice = self::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastInvoice && $lastInvoice->return_invoice) {
            $lastNumber = (int)substr($lastInvoice->return_invoice, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);

        return $prefix . $newNumber;
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id', 'id');
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(InwardReturnItem::class, 'inward_return_id', 'id');
    }

    public function inwardGatepass()
    {
        return $this->belongsTo(InwardGatepass::class, 'inward_gatepass_id');
    }
}
