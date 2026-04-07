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
        Schema::table('product_bookings', function (Blueprint $table) {
            $table->decimal('advance_payment', 12, 2)->default(0)->after('total_net');
        });
    }

    public function down()
    {
        Schema::table('product_bookings', function (Blueprint $table) {
            $table->dropColumn('advance_payment');
        });
    }
};
