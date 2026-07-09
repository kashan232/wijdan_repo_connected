<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = [
        'sales',
        'purchases',
        'sales_returns',
        'purchase_returns',
        'inward_gatepasses',
        'inward_returns',
        'expense_vouchers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && !Schema::hasColumn($table, 'accounting_period_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('accounting_period_id')->nullable()->after('id');
                    $blueprint->index('accounting_period_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'accounting_period_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropIndex(['accounting_period_id']);
                    $blueprint->dropColumn('accounting_period_id');
                });
            }
        }
    }
};
