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
        Schema::table('login_counts', function (Blueprint $table) {
            $table->integer('login_history_count')->default(10)->after('password_age');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('login_counts', function (Blueprint $table) {
            $table->dropColumn('login_history_count');
        });
    }
};
