<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_loan_payments', function (Blueprint $table) {
            $table->string('source')->default('manual')->after('type'); // manual, payroll_auto
            $table->unsignedBigInteger('payroll_id')->nullable()->after('source');
            $table->string('reference')->nullable()->after('payroll_id'); // Bank ref, voucher, etc.
        });
    }

    public function down(): void
    {
        Schema::table('hr_loan_payments', function (Blueprint $table) {
            $table->dropColumn(['source', 'payroll_id', 'reference']);
        });
    }
};
