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
        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->json('amortisation_schedule')->nullable()->after('fields_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->dropColumn('amortisation_schedule');
        });
    }
};
