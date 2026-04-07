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
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->string('absent_deduction_type')->default('manual')->after('leave_salary_per_day');
            // We can reuse leave_salary_per_day for the amount, or add a separate one if needed.
            // The user said "if user select manul than field will be ediable and user can itner value".
            // Since leave_salary_per_day is already there, I will just add the type.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_salary_structures', function (Blueprint $table) {
            $table->dropColumn('absent_deduction_type');
        });
    }
};
