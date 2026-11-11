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
        Schema::create('security_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('market_categories')->onDelete('cascade');
            $table->string('field_name');
            $table->enum('field_type', ['Float', 'Decimal', 'Int', 'Text']);
            $table->boolean('required')->default(0)->comment('1 = Required, 0 = Optional');
            $table->boolean('status')->default(1)->comment('1 = Active, 0 = Inactive');
            $table->string('created_by');
            $table->timestamps();
            
            // Indexes
            $table->index('category_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_management');
    }
};
