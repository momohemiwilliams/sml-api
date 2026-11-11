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
            $table->string('firstname')->after('name');
            $table->string('last_name')->after('firstname');
            $table->string('phone_number')->nullable()->after('email');
            $table->string('department')->nullable()->after('phone_number');
            $table->string('employee_id')->unique()->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('employee_id');
            $table->timestamp('last_login_at')->nullable()->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'firstname',
                'last_name',
                'phone_number',
                'department',
                'employee_id',
                'is_active',
                'last_login_at',
            ]);
        });
    }
};
