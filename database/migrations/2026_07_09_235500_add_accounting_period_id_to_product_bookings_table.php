<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_bookings') && !Schema::hasColumn('product_bookings', 'accounting_period_id')) {
            Schema::table('product_bookings', function (Blueprint $table) {
                $table->unsignedBigInteger('accounting_period_id')->nullable()->after('id');
                $table->index('accounting_period_id');
            });
        }

        $closedPeriods = DB::table('accounting_periods')->where('status', 'closed')->get();

        foreach ($closedPeriods as $period) {
            DB::table('product_bookings')
                ->whereNull('accounting_period_id')
                ->where(function ($q) use ($period) {
                    $q->where(function ($inner) use ($period) {
                        $inner->whereNotNull('booking_date')
                            ->whereDate('booking_date', '>=', $period->start_date)
                            ->whereDate('booking_date', '<=', $period->end_date);
                    })->orWhere(function ($inner) use ($period) {
                        $inner->whereNull('booking_date')
                            ->whereDate('created_at', '>=', $period->start_date)
                            ->whereDate('created_at', '<=', $period->end_date);
                    });
                })
                ->update(['accounting_period_id' => $period->id]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('product_bookings') && Schema::hasColumn('product_bookings', 'accounting_period_id')) {
            Schema::table('product_bookings', function (Blueprint $table) {
                $table->dropIndex(['accounting_period_id']);
                $table->dropColumn('accounting_period_id');
            });
        }
    }
};
