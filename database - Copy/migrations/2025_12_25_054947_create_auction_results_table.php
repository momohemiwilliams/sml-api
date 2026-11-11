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
        Schema::create('auction_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('security_id')->constrained()->onDelete('cascade');
            
            // Auction Information
            $table->string('auction_number')->unique();
            $table->date('auction_date');
            $table->date('value_date');
            $table->string('day_of_week')->nullable(); // Auto-calculated
            $table->integer('tenor_days'); // In days
            
            // Amounts
            $table->decimal('amount_offered', 20, 2);
            $table->decimal('amount_subscribed', 20, 2);
            $table->decimal('amount_allotted', 20, 2);
            $table->decimal('amount_sold', 20, 2);
            $table->decimal('non_competitive_sales', 20, 2)->default(0);
            $table->decimal('total_amount_sold', 20, 2); // Auto-calculated
            
            // Rates
            $table->decimal('stop_rate', 10, 4);
            $table->decimal('marginal_rate', 10, 4)->nullable();
            $table->decimal('true_yield', 10, 4)->nullable(); // Auto-calculated for T-Bills
            
            // Ratios
            $table->decimal('bid_cover_ratio', 10, 4)->nullable(); // Auto-calculated
            $table->decimal('subscription_level', 10, 2)->nullable(); // Percentage
            
            // Additional Information
            $table->string('auction_type')->default('Primary'); // Primary, Secondary
            $table->string('status')->default('Completed'); // Completed, Reopened, Cancelled
            $table->text('remarks')->nullable();
            
            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['security_id', 'auction_date']);
            $table->index('auction_date');
            $table->index('value_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auction_results');
    }
};
