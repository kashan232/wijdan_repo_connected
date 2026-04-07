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
    Schema::create('sales_returns', function (Blueprint $table) {
        $table->id();

        $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');

        // Match sales structure (with flexibility)
        $table->text('customer')->nullable();
        $table->text('reference')->nullable();
        $table->text('product')->nullable();
        $table->text('product_code')->nullable();
        $table->text('brand')->nullable();
        $table->text('unit')->nullable();
        $table->text('per_price')->nullable();
        $table->text('per_discount')->nullable();
        $table->text('qty')->nullable();
        $table->text('per_total')->nullable();

        $table->text('total_amount_Words')->nullable();
        $table->text('total_bill_amount')->nullable();
        $table->text('total_extradiscount')->nullable();
        $table->text('total_net')->nullable();

        $table->text('cash')->nullable();
        $table->text('card')->nullable();
        $table->text('change')->nullable();
        $table->text('color')->nullable();

        $table->text('total_items')->nullable();

        $table->text('return_note')->nullable(); // Reason for return

        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_returns');
    }
};
