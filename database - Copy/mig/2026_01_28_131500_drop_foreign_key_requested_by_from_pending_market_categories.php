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
        Schema::table('pending_market_categories', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign('pending_market_categories_requested_by_foreign');
            
            // Optionally ensure the column is just an integer (it likely is already)
            // $table->unsignedBigInteger('requested_by')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_market_categories', function (Blueprint $table) {
            // Restore the foreign key constraint (assuming local users table exists)
            $table->foreign('requested_by', 'pending_market_categories_requested_by_foreign')
                  ->references('id')->on('users');
        });
    }
};
