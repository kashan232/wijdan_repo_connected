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
        Schema::table('inward_gatepass_items', function (Blueprint $table) {
            $table->string('receive_type')->nullable(); // 'shop' or 'warehouse'
            $table->unsignedBigInteger('warehouse_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inward_gatepass_items', function (Blueprint $table) {
            $table->dropColumn(['receive_type', 'warehouse_id']);
        });
    }
};
