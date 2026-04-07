<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE hr_payrolls MODIFY COLUMN payroll_type ENUM('monthly', 'daily', 'commission') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("ALTER TABLE hr_payrolls MODIFY COLUMN payroll_type ENUM('monthly', 'daily') NOT NULL");
    }
};
