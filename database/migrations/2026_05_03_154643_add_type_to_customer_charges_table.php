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
        Schema::table('customer_charges', function (Blueprint $blueprint) {
            $blueprint->enum('type', ['plus', 'minus'])->default('plus')->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customer_charges', function (Blueprint $blueprint) {
            $blueprint->dropColumn('type');
        });
    }
};
