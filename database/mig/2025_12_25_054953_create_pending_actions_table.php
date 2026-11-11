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
        Schema::create('pending_actions', function (Blueprint $table) {
            $table->id();
            
            // Action Information
            $table->string('action_type'); // create, update, delete
            $table->string('model_type'); // Security, AuctionResult, ProductType, User
            $table->unsignedBigInteger('model_id')->nullable(); // ID of the record (null for create)
            
            // Data Storage
            $table->json('old_data')->nullable(); // Original data (for update/delete)
            $table->json('new_data')->nullable(); // New/Modified data (for create/update)
            $table->json('changes')->nullable(); // Specific changes made
            
            // Maker Information
            $table->foreignId('maker_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('submitted_at');
            $table->text('maker_remarks')->nullable();
            
            // Checker Information
            $table->foreignId('checker_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->timestamp('reviewed_at')->nullable();
            $table->text('checker_remarks')->nullable();
            
            // Email Notifications
            $table->boolean('maker_notified')->default(false);
            $table->boolean('checker_notified')->default(false);
            $table->timestamp('checker_notified_at')->nullable();
            
            // Additional Information
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['status', 'checker_id']);
            $table->index(['model_type', 'model_id']);
            $table->index('maker_id');
            $table->index('submitted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_actions');
    }
};
