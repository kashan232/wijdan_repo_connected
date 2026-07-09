<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeriodClosingSnapshot extends Model
{
    protected $fillable = [
        'accounting_period_id',
        'snapshot_type',
        'data',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }
}
