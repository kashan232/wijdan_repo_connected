<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseVoucher extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $casts = [
        'narration_id' => 'array',
        'row_account_head' => 'array',
        'row_account_id' => 'array',
        'amount' => 'array',
    ];

    /**
     * Generate auto EVID
     */
    public static function generateInvoiceNo()
    {
        $prefix = 'EVID-';
        $lastInvoice = self::orderBy('id', 'desc')->first();
        $lastNumber = 0;

        if ($lastInvoice && $lastInvoice->evid) {
            $lastNumber = (int)substr($lastInvoice->evid, strlen($prefix));
        }

        $newNumber = str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        return $prefix . $newNumber;
    }

    /**
     * Relations
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'party_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'party_id', 'id');
    }

    public function accountHeadType()
    {
        return $this->belongsTo(AccountHead::class, 'type', 'id');
    }

    public function partyAccount()
    {
        return $this->belongsTo(Account::class, 'party_id', 'id');
    }

    /**
     * Helper Attributes
     */
    public function getPartyNameAttribute()
    {
        if (is_numeric($this->type)) {
            return $this->partyAccount?->title ?? 'N/A';
        }
        if ($this->type === 'vendor') return $this->vendor?->name ?? 'N/A';
        if ($this->type === 'customer') return $this->customer?->customer_name ?? 'N/A';
        if ($this->type === 'walkin') return 'Walkin Customer';
        return 'N/A';
    }

    public function getTypeNameAttribute()
    {
        if (is_numeric($this->type)) {
            return $this->accountHeadType?->name ?? 'N/A';
        }
        return ucfirst($this->type);
    }

    public function getNarrationTextAttribute()
    {
        $ids = $this->narration_id;
        if (!$ids) return '';

        // Ensure it's array
        $ids = is_array($ids) ? $ids : json_decode($ids, true) ?? [];
        return Narration::whereIn('id', $ids)->pluck('narration')->implode(', ');
    }

    public function getRowTotalAmountAttribute()
    {
        $amounts = $this->amount;
        $amounts = is_array($amounts) ? $amounts : json_decode($amounts, true) ?? [];
        return array_sum($amounts);
    }

    public function getRowCountAttribute()
    {
        $amounts = $this->amount;
        $amounts = is_array($amounts) ? $amounts : json_decode($amounts, true) ?? [];
        return count($amounts);
    }
}
