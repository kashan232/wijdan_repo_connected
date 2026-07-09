<?php

namespace App\Services\PeriodClosing;

use App\Models\AccountingPeriod;
use App\Models\PeriodClosingSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PeriodClosingService
{
    private array $documentTables = [
        'sales' => 'created_at',
        'product_bookings' => 'booking_date',
        'purchases' => 'purchase_date',
        'sales_returns' => 'created_at',
        'purchase_returns' => 'return_date',
        'inward_gatepasses' => 'gatepass_date',
        'inward_returns' => 'return_date',
        'expense_vouchers' => 'entry_date',
    ];

    public function getSummary(?Carbon $closingDate = null): array
    {
        $closingDate = $closingDate ?? Carbon::today();
        $lastClosed = PeriodLock::getLastClosedEndDate();
        $periodStart = $this->resolvePeriodStart($lastClosed);

        $counts = [];
        foreach ($this->documentTables as $table => $dateColumn) {
            $counts[$table] = $this->countUntaggedRecords($table, $dateColumn, $periodStart, $closingDate);
        }

        return [
            'period_start' => $periodStart->format('Y-m-d'),
            'closing_date' => $closingDate->format('Y-m-d'),
            'last_closed_end' => $lastClosed?->format('Y-m-d'),
            'counts' => $counts,
            'total_records' => array_sum($counts),
            'closed_periods' => PeriodLock::getClosedPeriods(),
            'open_period' => PeriodLock::getOpenPeriod(),
        ];
    }

    public function closePeriod(Carbon $closingDate, int $userId, ?string $notes = null): AccountingPeriod
    {
        $closingDate = $closingDate->copy()->startOfDay();
        $lastClosed = PeriodLock::getLastClosedEndDate();

        if ($lastClosed && $closingDate->lte($lastClosed)) {
            throw new \RuntimeException(
                'Closing date pehle ki closed period se pehle ya barabar nahi ho sakti. Last closed: ' . $lastClosed->format('d M Y')
            );
        }

        if ($closingDate->isFuture()) {
            throw new \RuntimeException('Closing date future ki nahi ho sakti.');
        }

        $periodStart = $this->resolvePeriodStart($lastClosed);

        if ($closingDate->lt($periodStart)) {
            throw new \RuntimeException('Closing date period start se pehle nahi ho sakti.');
        }

        return DB::transaction(function () use ($closingDate, $periodStart, $userId, $notes) {
            $openPeriod = PeriodLock::getOpenPeriod();
            if ($openPeriod) {
                $openPeriod->update([
                    'end_date' => $closingDate->format('Y-m-d'),
                    'status' => 'closed',
                    'closed_by' => $userId,
                    'closed_at' => now(),
                    'notes' => $notes,
                ]);
                $closedPeriod = $openPeriod->fresh();
            } else {
                $closedPeriod = AccountingPeriod::create([
                    'name' => $this->buildPeriodName($periodStart, $closingDate),
                    'start_date' => $periodStart->format('Y-m-d'),
                    'end_date' => $closingDate->format('Y-m-d'),
                    'status' => 'closed',
                    'closed_by' => $userId,
                    'closed_at' => now(),
                    'notes' => $notes,
                ]);
            }

            $periodId = $closedPeriod->id;

            foreach ($this->documentTables as $table => $dateColumn) {
                $this->tagRecordsForPeriod($table, $dateColumn, $periodStart, $closingDate, $periodId);
            }

            $this->saveSnapshots($closedPeriod);

            $nextStart = $closingDate->copy()->addDay();
            AccountingPeriod::create([
                'name' => 'Open Period from ' . $nextStart->format('d M Y'),
                'start_date' => $nextStart->format('Y-m-d'),
                'end_date' => $nextStart->copy()->addYear()->format('Y-m-d'),
                'status' => 'open',
            ]);

            PeriodLock::clearCache();

            return $closedPeriod->fresh(['closedBy']);
        });
    }

    private function resolvePeriodStart(?Carbon $lastClosed): Carbon
    {
        $openPeriod = PeriodLock::getOpenPeriod();
        if ($openPeriod) {
            return $openPeriod->start_date->copy()->startOfDay();
        }

        if ($lastClosed) {
            return $lastClosed->copy()->addDay()->startOfDay();
        }

        $earliest = $this->findEarliestTransactionDate();
        if ($earliest) {
            return $earliest->copy()->startOfDay();
        }

        return Carbon::create(Carbon::now()->year, 1, 1)->startOfDay();
    }

    private function findEarliestTransactionDate(): ?Carbon
    {
        $dates = [];

        if (DB::table('sales')->exists()) {
            $dates[] = DB::table('sales')->min('created_at');
        }
        if (DB::table('purchases')->exists()) {
            $dates[] = DB::table('purchases')->min('purchase_date');
        }
        if (DB::table('expense_vouchers')->exists()) {
            $dates[] = DB::table('expense_vouchers')->min(DB::raw('COALESCE(entry_date, created_at)'));
        }
        if (DB::table('product_bookings')->exists()) {
            $dates[] = DB::table('product_bookings')->min(DB::raw('COALESCE(booking_date, created_at)'));
        }

        $dates = array_filter($dates);
        if (empty($dates)) {
            return null;
        }

        return Carbon::parse(min($dates));
    }

    private function countUntaggedRecords(string $table, string $dateColumn, Carbon $start, Carbon $end): int
    {
        $query = DB::table($table)->whereNull('accounting_period_id');

        $this->applyDateFilter($query, $table, $dateColumn, $start, $end);

        return $query->count();
    }

    private function tagRecordsForPeriod(
        string $table,
        string $dateColumn,
        Carbon $start,
        Carbon $end,
        int $periodId
    ): void {
        $query = DB::table($table)->whereNull('accounting_period_id');
        $this->applyDateFilter($query, $table, $dateColumn, $start, $end);
        $query->update(['accounting_period_id' => $periodId]);
    }

    private function applyDateFilter($query, string $table, string $dateColumn, Carbon $start, Carbon $end): void
    {
        if ($dateColumn === 'entry_date' && $table === 'expense_vouchers') {
            $query->where(function ($q) use ($start, $end) {
                $q->where(function ($inner) use ($start, $end) {
                    $inner->whereNotNull('entry_date')
                        ->whereDate('entry_date', '>=', $start->format('Y-m-d'))
                        ->whereDate('entry_date', '<=', $end->format('Y-m-d'));
                })->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNull('entry_date')
                        ->whereDate('created_at', '>=', $start->format('Y-m-d'))
                        ->whereDate('created_at', '<=', $end->format('Y-m-d'));
                });
            });
            return;
        }

        if ($dateColumn === 'booking_date' && $table === 'product_bookings') {
            $query->where(function ($q) use ($start, $end) {
                $q->where(function ($inner) use ($start, $end) {
                    $inner->whereNotNull('booking_date')
                        ->whereDate('booking_date', '>=', $start->format('Y-m-d'))
                        ->whereDate('booking_date', '<=', $end->format('Y-m-d'));
                })->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNull('booking_date')
                        ->whereDate('created_at', '>=', $start->format('Y-m-d'))
                        ->whereDate('created_at', '<=', $end->format('Y-m-d'));
                });
            });
            return;
        }

        if (in_array($dateColumn, ['created_at'], true)) {
            $query->whereDate($dateColumn, '>=', $start->format('Y-m-d'))
                ->whereDate($dateColumn, '<=', $end->format('Y-m-d'));
            return;
        }

        $query->whereDate($dateColumn, '>=', $start->format('Y-m-d'))
            ->whereDate($dateColumn, '<=', $end->format('Y-m-d'));
    }

    private function buildPeriodName(Carbon $start, Carbon $end): string
    {
        return $start->format('d M Y') . ' — ' . $end->format('d M Y');
    }

    private function saveSnapshots(AccountingPeriod $period): void
    {
        $periodId = $period->id;
        $endDate = $period->end_date->format('Y-m-d');

        $salesTotal = DB::table('sales')
            ->where('accounting_period_id', $periodId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(CAST(total_net AS DECIMAL(15,2))), 0) as total')
            ->first();

        $purchaseTotal = DB::table('purchases')
            ->where('accounting_period_id', $periodId)
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(net_amount), 0) as total')
            ->first();

        $expenseTotal = DB::table('expense_vouchers')
            ->where('accounting_period_id', $periodId)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(CAST(total_amount AS DECIMAL(15,2))), 0) as total')
            ->first();

        $customerBalances = DB::table('customer_ledgers')
            ->select('customer_id', DB::raw('MAX(closing_balance) as closing_balance'))
            ->groupBy('customer_id')
            ->get();

        $vendorBalances = DB::table('vendor_ledgers')
            ->select('vendor_id', DB::raw('MAX(closing_balance) as closing_balance'))
            ->groupBy('vendor_id')
            ->get();

        $snapshots = [
            ['snapshot_type' => 'sales_summary', 'data' => [
                'count' => (int) ($salesTotal->count ?? 0),
                'total' => (float) ($salesTotal->total ?? 0),
            ]],
            ['snapshot_type' => 'purchase_summary', 'data' => [
                'count' => (int) ($purchaseTotal->count ?? 0),
                'total' => (float) ($purchaseTotal->total ?? 0),
            ]],
            ['snapshot_type' => 'expense_summary', 'data' => [
                'count' => (int) ($expenseTotal->count ?? 0),
                'total' => (float) ($expenseTotal->total ?? 0),
            ]],
            ['snapshot_type' => 'customer_balances', 'data' => $customerBalances->toArray()],
            ['snapshot_type' => 'vendor_balances', 'data' => $vendorBalances->toArray()],
            ['snapshot_type' => 'document_counts', 'data' => $this->getPeriodDocumentCounts($periodId)],
        ];

        foreach ($snapshots as $snapshot) {
            PeriodClosingSnapshot::create([
                'accounting_period_id' => $periodId,
                'snapshot_type' => $snapshot['snapshot_type'],
                'data' => $snapshot['data'],
            ]);
        }
    }

    private function getPeriodDocumentCounts(int $periodId): array
    {
        $counts = [];
        foreach (array_keys($this->documentTables) as $table) {
            $counts[$table] = DB::table($table)->where('accounting_period_id', $periodId)->count();
        }

        return $counts;
    }
}
