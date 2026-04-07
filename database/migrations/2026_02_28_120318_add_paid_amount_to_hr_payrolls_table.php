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
            $table->decimal('paid_amount', 12, 2)->nullable()->after('net_salary');
            $table->string('payment_method')->nullable()->after('paid_amount');
            $table->text('payment_notes')->nullable()->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_payrolls', function (Blueprint $table) {
            //
        });
    }
};
