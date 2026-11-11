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
        Schema::create('securities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained()->onDelete('cascade');
            
            // Basic Information
            $table->string('isin', 12)->unique(); // International Securities Identification Number
            $table->string('security_name');
            $table->string('issuer');
            $table->string('issuer_category')->nullable();
            
            // Dates
            $table->date('issue_date');
            $table->date('maturity_date');
            $table->date('first_settlement_date')->nullable();
            $table->date('last_trading_date')->nullable();
            
            // Financial Details
            $table->decimal('face_value', 20, 2);
            $table->decimal('issue_price', 20, 2)->nullable();
            $table->decimal('coupon_rate', 10, 4)->nullable(); // For bonds
            $table->string('coupon_type')->nullable(); // Fixed, Floating, Zero
            $table->string('coupon_frequency')->nullable(); // Annual, Semi-Annual, Quarterly
            $table->decimal('discount_rate', 10, 4)->nullable(); // For bills
            
            // Calculated Fields
            $table->integer('tenor')->nullable(); // In years
            $table->decimal('effective_coupon', 10, 4)->nullable();
            $table->decimal('ttm', 10, 4)->nullable(); // Time to Maturity
            $table->string('day_count_basis')->nullable(); // Actual/360, Actual/365, etc.
            
            // Outstanding Values
            $table->decimal('outstanding_value', 20, 2)->default(0);
            $table->decimal('amount_issued', 20, 2)->nullable();
            $table->decimal('amount_outstanding', 20, 2)->nullable();
            
            // Rating Information
            $table->string('rating_agency')->nullable();
            $table->string('local_rating')->nullable();
            $table->string('global_rating')->nullable();
            $table->string('final_rating')->nullable(); // Concatenated rating
            
            // Additional Information
            $table->string('listing_status')->default('Listed'); // Listed, Unlisted
            $table->string('status')->default('Active'); // Active, Matured, Redeemed
            $table->text('remarks')->nullable();
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['product_type_id', 'status']);
            $table->index('issuer');
            $table->index('maturity_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('securities');
    }
};
