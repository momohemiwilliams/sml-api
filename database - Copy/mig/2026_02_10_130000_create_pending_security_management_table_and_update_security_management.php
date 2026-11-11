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
        // Add product_id to security_management
        Schema::table('security_management', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('category_id')->constrained('product_types')->nullOnDelete();
        });

        // Create pending_security_management table
        Schema::create('pending_security_management', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_management_id')->nullable()->constrained('security_management')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('market_categories')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('product_types')->nullOnDelete();
            $table->string('field_name')->nullable();
            $table->enum('field_type', ['Float', 'Decimal', 'Int', 'Text'])->nullable(); // Nullable for updates where not changing
            $table->boolean('required')->default(0)->nullable();
            $table->boolean('status')->default(1);
            $table->enum('request_type', ['create', 'update', 'delete']);
            $table->unsignedBigInteger('requested_by'); // Inputter
            $table->unsignedBigInteger('selected_authoriser_id')->nullable(); // Authoriser
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_security_management');

        Schema::table('security_management', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id');
        });
    }
};
