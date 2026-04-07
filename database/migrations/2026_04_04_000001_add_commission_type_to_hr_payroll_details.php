<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds 'commission' to the type ENUM column in hr_payroll_details.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE hr_payroll_details MODIFY COLUMN type ENUM('allowance', 'deduction', 'commission') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove commission type - WARNING: deletes commission rows first to avoid errors
        DB::statement("DELETE FROM hr_payroll_details WHERE type = 'commission'");
        DB::statement("ALTER TABLE hr_payroll_details MODIFY COLUMN type ENUM('allowance', 'deduction') NOT NULL");
    }
};
