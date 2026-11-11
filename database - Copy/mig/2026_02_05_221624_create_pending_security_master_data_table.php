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
        Schema::create('pending_security_master_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_master_id')->nullable()->constrained('security_master_data')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('market_categories')->onDelete('cascade');
            $table->string('security_name')->nullable();
            $table->boolean('status')->default(1);
            $table->json('fields_data')->nullable(); // Stores the dynamic field values
            $table->enum('request_type', ['create', 'update', 'delete']);
            $table->unsignedBigInteger('requested_by'); // Inputter
            $table->unsignedBigInteger('selected_authoriser_id')->nullable(); // Authoriser
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
        Schema::dropIfExists('pending_security_master_data');
    }
};
