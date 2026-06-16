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
        Schema::create('inward_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inward_gatepass_id')->nullable();
            $table->unsignedBigInteger('vendor_id');
            $table->string('return_invoice')->unique();
            $table->date('return_date');
            $table->text('return_reason')->nullable();
            $table->string('transport')->nullable();
            $table->string('vehicle_no')->nullable();
            $table->string('driver_name')->nullable();
            $table->string('delivery_person')->nullable();
            $table->unsignedBigInteger('warehouse_id')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('inward_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('inward_return_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 10, 2)->default(0);
            $table->string('receive_type')->nullable(); // warehouse or shop (from where it was returned)
            $table->text('note')->nullable();
            $table->timestamps();

            $table->foreign('inward_return_id')->references('id')->on('inward_returns')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inward_return_items');
        Schema::dropIfExists('inward_returns');
    }
};
