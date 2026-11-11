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
        Schema::table('security_master_data', function (Blueprint $table) {
            $table->string('lifecycle_status', 20)->default('active')->after('approval_status')->comment('active, called, matured');
            $table->date('redemption_date')->nullable()->after('lifecycle_status');
        });

        Schema::table('call_schedules', function (Blueprint $table) {
            $table->string('status', 20)->default('scheduled')->after('call_price')->comment('scheduled, exercised, not_exercised');
            $table->date('exercised_date')->nullable()->after('status');
            $table->decimal('exercised_price', 18, 4)->nullable()->after('exercised_date');
        });

        Schema::table('reset_schedules', function (Blueprint $table) {
            $table->string('status', 20)->default('scheduled')->after('spread')->comment('scheduled, applied, cancelled');
            $table->date('applied_date')->nullable()->after('status');
            $table->decimal('applied_rate', 18, 4)->nullable()->after('applied_date');
            $table->string('applied_benchmark')->nullable()->after('applied_rate');
            $table->decimal('applied_spread', 18, 4)->nullable()->after('applied_benchmark');
        });

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            // Allow new request types: call, reset
            $table->string('request_type', 20)->change();
            $table->json('event_data')->nullable()->after('reset_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->dropColumn('event_data');
            $table->enum('request_type', ['create', 'update', 'delete'])->change();
        });

        Schema::table('reset_schedules', function (Blueprint $table) {
            $table->dropColumn(['status', 'applied_date', 'applied_rate', 'applied_benchmark', 'applied_spread']);
        });

        Schema::table('call_schedules', function (Blueprint $table) {
            $table->dropColumn(['status', 'exercised_date', 'exercised_price']);
        });

        Schema::table('security_master_data', function (Blueprint $table) {
            $table->dropColumn(['lifecycle_status', 'redemption_date']);
        });
    }
};
