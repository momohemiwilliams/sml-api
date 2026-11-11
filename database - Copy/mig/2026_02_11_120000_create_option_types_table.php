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
        Schema::create('option_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Option-Free", "Callable"
            $table->string('code')->unique(); // e.g., "OPTION_FREE", "CALLABLE"
            $table->text('description')->nullable();
            
            // Configuration flags for frontend inputs
            $table->boolean('has_call_date')->default(false)->comment('If true, show Call Date input');
            
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_types');
    }
};
