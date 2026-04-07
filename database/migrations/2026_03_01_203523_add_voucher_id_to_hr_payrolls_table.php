<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->unsignedBigInteger('voucher_id')->nullable()->after('payment_date')->comment('Auto-created payment voucher when salary is paid');
            $table->unsignedBigInteger('salary_account_id')->nullable()->after('voucher_id')->comment('Employee individual salary tracking account');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            $table->dropColumn(['voucher_id', 'salary_account_id']);
        });
    }
};
