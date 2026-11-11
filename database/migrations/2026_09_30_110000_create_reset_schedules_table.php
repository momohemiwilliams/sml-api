<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('option_types', function (Blueprint $table) {
            $table->boolean('has_reset_date')->default(false)->after('has_call_date')->comment('If true, require Reset Schedule upload');
        });

        Schema::create('reset_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_master_id')->constrained('security_master_data')->onDelete('cascade');
            $table->date('reset_date')->nullable();
            $table->decimal('new_coupon_rate', 18, 4)->nullable();
            $table->string('benchmark')->nullable();
            $table->decimal('spread', 18, 4)->nullable();
            $table->timestamps();
        });

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->json('reset_schedule')->nullable()->after('call_schedule');
        });

        // Add the Perpetual option type
        if (!DB::table('option_types')->where('code', 'PERPETUAL')->exists()) {
            DB::table('option_types')->insert([
                'name' => 'Perpetual',
                'code' => 'PERPETUAL',
                'description' => 'Bond with no maturity date whose coupon resets on scheduled dates',
                'has_call_date' => false,
                'has_reset_date' => true,
                'is_active' => true,
                'created_by' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('option_types')->where('code', 'PERPETUAL')->delete();

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->dropColumn('reset_schedule');
        });

        Schema::dropIfExists('reset_schedules');

        Schema::table('option_types', function (Blueprint $table) {
            $table->dropColumn('has_reset_date');
        });
    }
};
