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
        Schema::create('pending_users', function (Blueprint $table) {
            $table->id();
            $table->string('firstname');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('department')->nullable();
            $table->string('employee_id')->nullable();
            $table->string('role'); // Store the role name requested
            $table->string('password')->nullable(); // Store hashed password if needed, or null
            $table->foreignId('requested_by')->constrained('users');
            $table->string('approval_status')->default('pending'); // pending, rejected
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_users');
    }
};
