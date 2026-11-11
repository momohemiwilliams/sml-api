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
        Schema::table('securities', function (Blueprint $table) {
            // Add new columns
            $table->string('issue_category')->nullable()->after('product_type_id');
            $table->foreignId('security_type_id')->nullable()->after('issue_category')->constrained('security_types')->onDelete('set null');
            $table->text('description')->nullable()->after('security_name');
            
            // Rename columns to match pending_securities structure
            $table->renameColumn('security_name', 'security_name_old');
            $table->renameColumn('issuer_category', 'issuer_category_old');
            $table->renameColumn('coupon_rate', 'coupon');
            $table->renameColumn('rating_agency', 'rating_1_agency');
            $table->renameColumn('local_rating', 'rating_1');
            $table->renameColumn('global_rating', 'rating_2');
            $table->renameColumn('day_count_basis', 'day_count_convention');
            
            // Add floating rate fields
            $table->decimal('frm', 10, 4)->nullable()->after('coupon_type'); // Floating Rate Margin
            $table->string('frb')->nullable()->after('frm'); // Floating Rate Benchmark
            $table->decimal('frbv', 10, 4)->nullable()->after('frb'); // Floating Rate Benchmark Value
            $table->decimal('coupon_floor', 10, 4)->nullable()->after('frbv');
            $table->decimal('coupon_cap', 10, 4)->nullable()->after('coupon_floor');
            
            // Add missing rating fields
            $table->date('rating_1_issuance_date')->nullable()->after('rating_1');
            $table->date('rating_1_expiration_date')->nullable()->after('rating_1_issuance_date');
            $table->string('rating_2_agency')->nullable()->after('rating_1_expiration_date');
            $table->date('rating_2_issuance_date')->nullable()->after('rating_2');
            $table->date('rating_2_expiration_date')->nullable()->after('rating_2_issuance_date');
            
            // Add other missing fields
            $table->decimal('fgn_benchmark_yield', 10, 4)->nullable()->after('effective_coupon');
            $table->decimal('issue_size', 20, 2)->nullable()->after('fgn_benchmark_yield');
            $table->integer('day_count_basis')->nullable()->after('day_count_convention');
            $table->string('option_type')->nullable()->after('day_count_basis');
            $table->date('call_date')->nullable()->after('option_type');
            $table->string('yield_at_issue')->nullable()->after('call_date');
            $table->date('interest_determination_date')->nullable()->after('yield_at_issue');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('securities', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn([
                'issue_category',
                'security_type_id',
                'description',
                'frm',
                'frb',
                'frbv',
                'coupon_floor',
                'coupon_cap',
                'rating_1_issuance_date',
                'rating_1_expiration_date',
                'rating_2_agency',
                'rating_2_issuance_date',
                'rating_2_expiration_date',
                'fgn_benchmark_yield',
                'issue_size',
                'day_count_basis',
                'option_type',
                'call_date',
                'yield_at_issue',
                'interest_determination_date',
            ]);
            
            // Rename columns back
            $table->renameColumn('security_name_old', 'security_name');
            $table->renameColumn('issuer_category_old', 'issuer_category');
            $table->renameColumn('coupon', 'coupon_rate');
            $table->renameColumn('rating_1_agency', 'rating_agency');
            $table->renameColumn('rating_1', 'local_rating');
            $table->renameColumn('rating_2', 'global_rating');
            $table->renameColumn('day_count_convention', 'day_count_basis');
        });
    }
};
