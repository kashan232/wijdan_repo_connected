<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'charge_no',
        'amount',
        'type',
        'date',
        'vehicle_no',
        'transporter_name',
        'note',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
