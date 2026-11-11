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
        Schema::table('amortisation_schedules', function (Blueprint $table) {
            $table->timestamp('processed_at')->nullable()->after('outstanding_amount')->comment('When the outstanding value was applied to the security');
        });

        Schema::table('call_schedules', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('exercised_price');
        });

        Schema::table('reset_schedules', function (Blueprint $table) {
            $table->timestamp('reminded_at')->nullable()->after('applied_spread');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reset_schedules', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });

        Schema::table('call_schedules', function (Blueprint $table) {
            $table->dropColumn('reminded_at');
        });

        Schema::table('amortisation_schedules', function (Blueprint $table) {
            $table->dropColumn('processed_at');
        });
    }
};
