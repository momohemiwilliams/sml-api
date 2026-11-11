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
        Schema::create('pending_auction_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('auction_result_id')->nullable(); // For updates/deletes
            $table->unsignedBigInteger('security_id');
            
            // Auction Information
            $table->string('auction_number')->nullable();
            $table->date('auction_date');
            $table->date('value_date');
            $table->string('day_of_week')->nullable();
            $table->integer('tenor_days');
            
            // Amounts
            $table->decimal('amount_offered', 20, 2);
            $table->decimal('amount_subscribed', 20, 2);
            $table->decimal('amount_allotted', 20, 2);
            $table->decimal('amount_sold', 20, 2);
            $table->decimal('non_competitive_sales', 20, 2)->default(0);
            $table->decimal('total_amount_sold', 20, 2);
            
            // Rates
            $table->decimal('stop_rate', 10, 4);
            $table->decimal('marginal_rate', 10, 4)->nullable();
            $table->decimal('true_yield', 10, 4)->nullable();
            
            // Ratios
            $table->decimal('bid_cover_ratio', 10, 4)->nullable();
            $table->decimal('subscription_level', 10, 2)->nullable();
            
            // Additional Information
            $table->string('auction_type')->default('Primary');
            $table->string('status')->default('Completed');
            $table->text('remarks')->nullable();
            
            // Approval Process Fields
            $table->string('request_type'); // create, update, delete
            $table->unsignedBigInteger('requested_by'); // User ID
            $table->unsignedBigInteger('selected_authoriser_id')->nullable(); // User ID
            $table->string('approval_status')->default('pending'); // pending, approved, rejected
            $table->text('rejection_reason')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('approval_status');
            $table->index('requested_by');
            $table->index('selected_authoriser_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pending_auction_results');
    }
};
