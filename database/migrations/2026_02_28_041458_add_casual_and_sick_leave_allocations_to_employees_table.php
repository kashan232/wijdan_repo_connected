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
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->integer('casual_leaves_allocated')->default(0)->after('is_docs_submitted');
            $table->integer('sick_leaves_allocated')->default(0)->after('casual_leaves_allocated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hr_employees', function (Blueprint $table) {
            $table->dropColumn(['casual_leaves_allocated', 'sick_leaves_allocated']);
        });
    }
};
