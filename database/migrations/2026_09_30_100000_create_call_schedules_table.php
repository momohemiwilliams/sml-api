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
        Schema::create('call_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_master_id')->constrained('security_master_data')->onDelete('cascade');
            $table->date('call_date')->nullable();
            $table->decimal('call_price', 18, 4)->nullable();
            $table->timestamps();
        });

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->json('call_schedule')->nullable()->after('amortisation_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->dropColumn('call_schedule');
        });

        Schema::dropIfExists('call_schedules');
    }
};
