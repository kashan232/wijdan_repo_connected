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
            $table->decimal('subtotal', 15, 2)->nullable()->after('remarks');
            $table->decimal('discount', 15, 2)->nullable()->after('subtotal');
            $table->decimal('extra_cost', 15, 2)->nullable()->after('discount');
            $table->decimal('net_amount', 15, 2)->nullable()->after('extra_cost');
            $table->decimal('paid_amount', 15, 2)->nullable()->after('net_amount');
            $table->decimal('due_amount', 15, 2)->nullable()->after('paid_amount');
            $table->text('note')->nullable()->after('due_amount');
            $table->string('bill_status')->default('unbilled')->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            $table->dropColumn([
                'subtotal',
                'discount',
                'extra_cost',
                'net_amount',
                'paid_amount',
                'due_amount',
                'note',
                'bill_status'
            ]);
        });
    }
};
