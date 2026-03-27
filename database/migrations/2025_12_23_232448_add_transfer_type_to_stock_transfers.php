<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up()
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('transfer_to')->after('from_warehouse_id'); 
            // warehouse | shop

            $table->unsignedBigInteger('to_warehouse_id')->nullable()->change();
            $table->string('shop_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            //
        });
    }
};
