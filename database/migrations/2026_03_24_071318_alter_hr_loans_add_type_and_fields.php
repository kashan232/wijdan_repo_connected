<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_loans', function (Blueprint $table) {
            $table->enum('loan_type', ['salary_deduction', 'self_paid'])->default('salary_deduction')->after('employee_id');
            $table->integer('total_installments')->nullable()->after('installment_amount');
            $table->integer('installments_paid')->default(0)->after('total_installments');
            $table->string('start_month', 7)->nullable()->after('installments_paid'); // YYYY-MM
            $table->string('expected_end_month', 7)->nullable()->after('start_month'); // YYYY-MM
            $table->date('disbursed_at')->nullable()->after('expected_end_month');
            $table->date('approved_at')->nullable()->after('disbursed_at');
            $table->unsignedBigInteger('approved_by')->nullable()->after('approved_at');
            $table->text('notes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('hr_loans', function (Blueprint $table) {
            $table->dropColumn([
                'loan_type', 'total_installments', 'installments_paid',
                'start_month', 'expected_end_month', 'disbursed_at',
                'approved_at', 'approved_by', 'notes',
            ]);
        });
    }
};
