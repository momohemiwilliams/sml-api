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
        Schema::table('pending_security_management', function (Blueprint $table) {
            // Drop foreign key constraints to allow pending IDs (ghost IDs)
            if (config('database.default') !== 'sqlite') {
                $table->dropForeign(['category_id']);
                $table->dropForeign(['product_id']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_security_management', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('market_categories')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('product_types')->onDelete('set null');
        });
    }
};
