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
        Schema::create('pending_product_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_type_id')->nullable()->comment('Null for create requests');
            $table->foreignId('market_category_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('request_type', ['create', 'update', 'delete']);
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('selected_authoriser_id')->constrained('users')->comment('The specific authoriser selected by the inputter');
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
        Schema::dropIfExists('pending_product_types');
    }
};
