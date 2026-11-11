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
            $table->foreignId('product_id')->nullable()->after('category_id')->constrained('product_types')->nullOnDelete();
        });

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('category_id')->constrained('product_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_master_data', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });

        Schema::table('pending_security_master_data', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
