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
        Schema::table('inward_returns', function (Blueprint $table) {
            $table->decimal('subtotal', 15, 2)->default(0)->after('delivery_person');
            $table->decimal('item_discount', 15, 2)->default(0)->after('subtotal');
            $table->decimal('discount', 15, 2)->default(0)->after('item_discount');
            $table->decimal('overall_discount', 15, 2)->default(0)->after('discount');
            $table->decimal('net_amount', 15, 2)->default(0)->after('overall_discount');
        });

        Schema::table('inward_return_items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->nullable()->after('qty');
            $table->decimal('discount_value', 15, 2)->default(0)->after('price');
            $table->string('discount_type')->default('pkr')->after('discount_value');
            $table->decimal('discount_amount', 15, 2)->default(0)->after('discount_type');
            $table->decimal('total', 15, 2)->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inward_returns', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'item_discount', 'discount', 'overall_discount', 'net_amount']);
        });

        Schema::table('inward_return_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'discount_value', 'discount_type', 'discount_amount', 'total']);
        });
    }
};
