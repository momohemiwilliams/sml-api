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
        Schema::create('amortisation_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_master_id')->constrained('security_master_data')->onDelete('cascade');
            $table->date('date')->nullable();
            $table->decimal('scheduled_coupon_cashflow', 18, 4)->nullable();
            $table->decimal('scheduled_principal_cashflow', 18, 4)->nullable();
            $table->decimal('outstanding_amount', 18, 4)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('amortisation_schedules');
    }
};
