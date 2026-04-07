<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('head_id');
            $table->string('account_code')->unique(); // e.g., ACC001
            $table->string('title'); // Account title e.g., UBL Current
            $table->enum('type', ['Debit', 'Credit']); // Debit or Credit
            $table->decimal('opening_balance', 15, 2)->default(0); // optional
            $table->boolean('status')->default(1); // Active/Inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
