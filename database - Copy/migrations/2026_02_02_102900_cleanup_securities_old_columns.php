<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate data from old columns to new columns
        DB::statement('UPDATE securities SET description = security_name_old WHERE security_name_old IS NOT NULL AND description IS NULL');
        DB::statement('UPDATE securities SET issue_category = issuer_category_old WHERE issuer_category_old IS NOT NULL AND issue_category IS NULL');
        
        // Drop old columns
        Schema::table('securities', function (Blueprint $table) {
            $table->dropColumn(['security_name_old', 'issuer_category_old']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('securities', function (Blueprint $table) {
            $table->string('security_name_old')->nullable();
            $table->string('issuer_category_old')->nullable();
        });
    }
};
