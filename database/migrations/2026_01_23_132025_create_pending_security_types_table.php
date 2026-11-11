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
        Schema::create('pending_security_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('security_type_id')->nullable()->comment('Null for create requests');
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('request_type', ['create', 'update', 'delete']);
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('selected_authoriser_id')->constrained('users');
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
        Schema::dropIfExists('pending_security_types');
    }
};
