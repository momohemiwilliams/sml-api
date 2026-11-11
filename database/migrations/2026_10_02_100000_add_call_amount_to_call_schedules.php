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
        Schema::table('call_schedules', function (Blueprint $table) {
            // Principal retired by the call, in the same units as Outstanding Value (₦'bn). Null = full call.
            $table->decimal('call_amount', 18, 4)->nullable()->after('call_price');
            $table->decimal('exercised_amount', 18, 4)->nullable()->after('exercised_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('call_schedules', function (Blueprint $table) {
            $table->dropColumn(['call_amount', 'exercised_amount']);
        });
    }
};
