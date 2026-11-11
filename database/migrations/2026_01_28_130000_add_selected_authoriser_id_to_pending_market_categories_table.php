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
        Schema::table('pending_market_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('selected_authoriser_id')->nullable()->after('requested_by');
            // We assume users table exists, but foreign key might be tricky if user is remote (?)
            // But checking SecurityService, it seems `selected_authoriser_id` is just an ID. 
            // In PendingSecurity, it has a foreign key to users table locally?
            // "The user has 1 active workspaces... c:\xampp\htdocs\smlars"
            // The previous conversation mentioned local user lookups being replaced by external, 
            // BUT the models (PendingSecurity) still have `belongsTo(User::class)`.
            // So a local users table exists. I will add the column as integer.
            // I'll skip the foreign key constraint to be safe against data inconsistencies if external IDs don't match local,
            // ALTHOUGH the error message shows we are inserting ID 20.
            // Let's just add the column for now.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pending_market_categories', function (Blueprint $table) {
            $table->dropColumn('selected_authoriser_id');
        });
    }
};
