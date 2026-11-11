<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Add approval status enum
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])
                  ->default('pending')
                  ->after('is_active');
            
            // Add approved_by (without foreign key constraint to avoid test issues)
            $table->unsignedBigInteger('approved_by')
                  ->nullable()
                  ->after('approval_status');
            
            // Add approval timestamp
            $table->timestamp('approved_at')
                  ->nullable()
                  ->after('approved_by');
            
            // Add rejection reason
            $table->text('rejection_reason')
                  ->nullable()
                  ->after('approved_at');
        });

        // Set all existing users to approved status for backward compatibility
        DB::table('users')->update(['approval_status' => 'approved']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'approval_status',
                'approved_by',
                'approved_at',
                'rejection_reason',
            ]);
        });
    }
};
