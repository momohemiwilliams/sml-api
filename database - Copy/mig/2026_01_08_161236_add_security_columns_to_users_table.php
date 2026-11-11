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
        Schema::table('users', function (Blueprint $table) {
            $table->integer('failed_logins')->default(0);
            $table->timestamp('lockout_time')->nullable();
            $table->timestamp('password_changed_at')->nullable();
            $table->string('usertype')->default('internal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['failed_logins', 'lockout_time', 'password_changed_at', 'usertype']);
        });
    }
};
