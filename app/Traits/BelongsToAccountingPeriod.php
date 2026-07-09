<?php

namespace App\Traits;

use App\Services\PeriodClosing\PeriodLock;

trait BelongsToAccountingPeriod
{
    public static function bootBelongsToAccountingPeriod(): void
    {
        static::creating(function ($model) {
            if (empty($model->accounting_period_id)) {
                $openPeriodId = PeriodLock::getOpenPeriodId();
                if ($openPeriodId) {
                    $model->accounting_period_id = $openPeriodId;
                }
            }
        });
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(\App\Models\AccountingPeriod::class, 'accounting_period_id');
    }
}
