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
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            $table->dropColumn('invoice_no');
        });
    }
};
