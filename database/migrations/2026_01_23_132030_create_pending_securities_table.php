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
        Schema::create('pending_securities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_id')->nullable()->constrained('securities')->onDelete('cascade');
            
            // Field 1: Issue Category
            $table->string('issue_category')->nullable();
            
            // Field 2: Issuer
            $table->string('issuer')->nullable();
            
            // Field 3: Security Type (Foreign Key)
            $table->foreignId('security_type_id')->nullable()->constrained('security_types')->onDelete('set null');
            
            // Field 4: ISIN
            $table->string('isin', 12)->nullable();
            
            // Field 5: Description
            $table->text('description')->nullable();
            
            // Field 6: Issue Date
            $table->date('issue_date')->nullable();
            
            // Field 7: Maturity Date
            $table->date('maturity_date')->nullable();
            
            // Field 8: Tenor (Calculated)
            $table->integer('tenor')->nullable();
            
            // Field 9: Coupon (%)
            $table->decimal('coupon', 10, 4)->nullable();
            
            // Field 10: Coupon Type (Dropdown: Fixed/Floating)
            $table->string('coupon_type')->nullable();
            
            // Floating Rate Fields (conditional on Coupon Type = Floating)
            $table->decimal('frm', 10, 4)->nullable(); // Floating Rate Margin
            $table->string('frb')->nullable(); // Floating Rate Benchmark
            $table->decimal('frbv', 10, 4)->nullable(); // Floating Rate Benchmark Value
            $table->decimal('coupon_floor', 10, 4)->nullable(); // CF
            $table->decimal('coupon_cap', 10, 4)->nullable(); // CC
            
            // Field 11: Coupon Frequency
            $table->integer('coupon_frequency')->nullable();
            
            // Field 12: Effective Coupon (Calculated)
            $table->decimal('effective_coupon', 10, 4)->nullable();
            
            // Field 13: FGN Benchmark Yield at Issue (%)
            $table->decimal('fgn_benchmark_yield', 10, 4)->nullable();
            
            // Field 14: Issue Size (₦'bn)
            $table->decimal('issue_size', 20, 2)->nullable();
            
            // Field 15: Outstanding Value (₦'bn)
            $table->decimal('outstanding_value', 20, 2)->nullable();
            
            // Field 16: TTM (Calculated)
            $table->decimal('ttm', 10, 4)->nullable();
            
            // Field 17: Day Count Convention (Dropdown)
            $table->string('day_count_convention')->nullable();
            
            // Field 18: Day Count Basis (Auto-filled from Convention)
            $table->integer('day_count_basis')->nullable();
            
            // Field 19: Option Type (Dropdown)
            $table->string('option_type')->nullable();
            
            // Call Date (conditional on Option Type = Callable)
            $table->date('call_date')->nullable();
            
            // Field 20: Yield at Issue
            $table->string('yield_at_issue')->nullable();
            
            // Field 21: Interest Determination Date
            $table->date('interest_determination_date')->nullable();
            
            // Field 23: Listing Status (Dropdown)
            $table->string('listing_status')->nullable();
            
            // Field 24-27: Rating 1
            $table->string('rating_1_agency')->nullable();
            $table->string('rating_1')->nullable();
            $table->date('rating_1_issuance_date')->nullable();
            $table->date('rating_1_expiration_date')->nullable();
            
            // Field 28-31: Rating 2
            $table->string('rating_2_agency')->nullable();
            $table->string('rating_2')->nullable();
            $table->date('rating_2_issuance_date')->nullable();
            $table->date('rating_2_expiration_date')->nullable();
            
            // Field 32: Final Rating (Calculated)
            $table->text('final_rating')->nullable();
            
            // Additional fields from existing securities table
            $table->foreignId('product_type_id')->nullable()->constrained()->onDelete('cascade');
            $table->date('first_settlement_date')->nullable();
            $table->date('last_trading_date')->nullable();
            $table->decimal('face_value', 20, 2)->nullable();
            $table->decimal('issue_price', 20, 2)->nullable();
            $table->decimal('discount_rate', 10, 4)->nullable();
            $table->decimal('amount_issued', 20, 2)->nullable();
            $table->decimal('amount_outstanding', 20, 2)->nullable();
            $table->string('status')->nullable();
            $table->text('remarks')->nullable();
            
            // Approval Workflow Fields
            $table->string('request_type'); // create, update, delete
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('selected_authoriser_id')->constrained('users');
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('approval_status');
            $table->index('request_type');
            $table->index('selected_authoriser_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_securities');
    }
};
