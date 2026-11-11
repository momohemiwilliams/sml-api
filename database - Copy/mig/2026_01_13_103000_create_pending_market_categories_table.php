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
        Schema::create('pending_market_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('market_category_id')->nullable()->constrained('market_categories')->onDelete('cascade');
            
            // Fields to be changed/created
            $table->string('name')->nullable();
            $table->string('code', 10)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->nullable();
            
            // Request Meta
            $table->string('request_type'); // create, update, delete
            $table->foreignId('requested_by')->constrained('users');
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_market_categories');
    }
};
