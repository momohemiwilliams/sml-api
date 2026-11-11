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
        Schema::table('auction_results', function (Blueprint $table) {
            // Drop old foreign key referencing securities table
            $table->dropForeign(['security_id']);
            
            // Add new foreign key referencing security_master_data table
            $table->foreign('security_id')
                  ->references('id')
                  ->on('security_master_data')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('auction_results', function (Blueprint $table) {
            $table->dropForeign(['security_id']);
            
            // Revert strict reference (assuming securities table exists and was the intended target)
            // If securities table is gone, this down method might fail, but that's expected for this fix.
            if (Schema::hasTable('securities')) {
                $table->foreign('security_id')
                      ->references('id')
                      ->on('securities')
                      ->onDelete('cascade');
            }
        });
    }
};
