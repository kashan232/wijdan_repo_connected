<?php

namespace App\Http\Controllers\Concerns;

use App\Services\PeriodClosing\PeriodLock;
use Illuminate\Database\Eloquent\Model;

trait GuardsClosedPeriod
{
    protected function guardClosedPeriodByDate($date): void
    {
        PeriodLock::assertDateNotClosed($date);
    }

    protected function guardClosedPeriodRecord(?Model $record): void
    {
        if (!$record) {
            return;
        }

        PeriodLock::assertPeriodNotClosed($record->accounting_period_id ?? null);

        $date = $this->resolveRecordDate($record);
        if ($date) {
            PeriodLock::assertDateNotClosed($date);
        }
    }

    private function resolveRecordDate(Model $record): mixed
    {
        return match (true) {
            isset($record->purchase_date) => $record->purchase_date,
            isset($record->return_date) => $record->return_date,
            isset($record->gatepass_date) => $record->gatepass_date,
            isset($record->entry_date) => $record->entry_date,
            isset($record->booking_date) => $record->booking_date,
            default => $record->created_at ?? null,
        };
    }
}
