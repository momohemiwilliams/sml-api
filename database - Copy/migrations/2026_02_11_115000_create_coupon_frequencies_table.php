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
        Schema::create('coupon_frequencies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Annually", "Semi-annually"
            $table->string('code')->unique(); // e.g., "ANNUALLY", "SEMI_ANNUALLY"
            $table->integer('frequency_per_year')->unsigned(); // e.g., 1, 2, 4, 12
            $table->text('description')->nullable();
            
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
        Schema::dropIfExists('coupon_frequencies');
    }
};
