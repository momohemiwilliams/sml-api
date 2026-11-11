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
        Schema::table('security_management', function (Blueprint $table) {
            $table->string('field_type', 50)->change();
        });

        Schema::table('pending_security_management', function (Blueprint $table) {
            $table->string('field_type', 50)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_management', function (Blueprint $table) {
            $table->enum('field_type', ['Float', 'Decimal', 'Int', 'Text'])->change();
        });

        Schema::table('pending_security_management', function (Blueprint $table) {
            $table->enum('field_type', ['Float', 'Decimal', 'Int', 'Text'])->nullable()->change();
        });
    }
};
