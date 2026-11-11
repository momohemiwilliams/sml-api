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
        Schema::create('security_master_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_master_id')->constrained('security_master_data')->onDelete('cascade');
            $table->foreignId('field_id')->constrained('security_management')->onDelete('cascade');
            $table->text('field_value')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('security_master_id');
            $table->index('field_id');
            
            // Unique constraint to prevent duplicate field entries for the same security
            $table->unique(['security_master_id', 'field_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_master_field_values');
    }
};
