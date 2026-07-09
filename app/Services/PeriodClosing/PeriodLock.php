<?php

namespace App\Services\PeriodClosing;

use App\Models\AccountingPeriod;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class PeriodLock
{
    public static function getOpenPeriod(): ?AccountingPeriod
    {
        return Cache::remember('accounting_period_open', 60, function () {
            return AccountingPeriod::where('status', 'open')->orderByDesc('id')->first();
        });
    }

    public static function getOpenPeriodId(): ?int
    {
        return static::getOpenPeriod()?->id;
    }

    public static function getLastClosedPeriod(): ?AccountingPeriod
    {
        return Cache::remember('accounting_period_last_closed', 60, function () {
            return AccountingPeriod::where('status', 'closed')->orderByDesc('end_date')->first();
        });
    }

    public static function getLastClosedEndDate(): ?Carbon
    {
        $period = static::getLastClosedPeriod();

        return $period ? $period->end_date->copy()->endOfDay() : null;
    }

    public static function isDateInClosedPeriod($date): bool
    {
        if (!$date) {
            return false;
        }

        $parsed = Carbon::parse($date);
        $lastClosedEnd = static::getLastClosedEndDate();

        if (!$lastClosedEnd) {
            return false;
        }

        return $parsed->lte($lastClosedEnd);
    }

    public static function isPeriodClosed(?int $periodId): bool
    {
        if (!$periodId) {
            return false;
        }

        $period = AccountingPeriod::find($periodId);

        return $period && $period->isClosed();
    }

    public static function assertDateNotClosed($date, string $message = null): void
    {
        if (static::isDateInClosedPeriod($date)) {
            $end = static::getLastClosedEndDate()?->format('d M Y');
            throw new \RuntimeException(
                $message ?? "Yeh period band ho chuka hai (closing date: {$end}). Edit ya delete nahi ho sakta."
            );
        }
    }

    public static function assertPeriodNotClosed(?int $periodId, string $message = null): void
    {
        if (static::isPeriodClosed($periodId)) {
            throw new \RuntimeException(
                $message ?? 'Yeh record band period ka hai. Edit ya delete nahi ho sakta.'
            );
        }
    }

    public static function clearCache(): void
    {
        Cache::forget('accounting_period_open');
        Cache::forget('accounting_period_last_closed');
        Cache::forget('accounting_period_closed_ids');
    }

    public static function getClosedPeriodIds(): array
    {
        return Cache::remember('accounting_period_closed_ids', 60, function () {
            return AccountingPeriod::where('status', 'closed')->pluck('id')->toArray();
        });
    }

    /**
     * Main system lists: hide closed period records.
     * Shows open period + any untagged records after closing.
     */
    public static function applyOpenPeriodFilter($query, ?string $table = null)
    {
        $column = $table ? "{$table}.accounting_period_id" : 'accounting_period_id';
        $closedIds = static::getClosedPeriodIds();

        if (empty($closedIds)) {
            return $query;
        }

        return $query->where(function ($q) use ($column, $closedIds) {
            $q->whereNull($column)->orWhereNotIn($column, $closedIds);
        });
    }

    public static function getClosedPeriods()
    {
        return AccountingPeriod::where('status', 'closed')->orderByDesc('end_date')->get();
    }
}
