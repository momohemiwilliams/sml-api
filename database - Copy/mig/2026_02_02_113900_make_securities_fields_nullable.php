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
        Schema::table('securities', function (Blueprint $table) {
            // Make all required fields nullable to match pending_securities structure
            $table->string('isin', 12)->nullable()->change();
            $table->date('issue_date')->nullable()->change();
            $table->date('maturity_date')->nullable()->change();
            $table->decimal('face_value', 20, 2)->nullable()->change();
            $table->foreignId('product_type_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('securities', function (Blueprint $table) {
            $table->string('isin', 12)->nullable(false)->change();
            $table->date('issue_date')->nullable(false)->change();
            $table->date('maturity_date')->nullable(false)->change();
            $table->decimal('face_value', 20, 2)->nullable(false)->change();
            $table->foreignId('product_type_id')->nullable(false)->change();
        });
    }
};
