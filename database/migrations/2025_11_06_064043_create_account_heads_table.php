<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_heads', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // Head name e.g., Bank, Expense
            $table->string('type')->nullable(); // Optional: Debit/Credit for default type
            $table->boolean('status')->default(1); // Active/Inactive
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_heads');
    }
};

