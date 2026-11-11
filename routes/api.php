<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SecurityController;
use App\Http\Controllers\Api\BenchmarkController;
use App\Http\Controllers\Api\Admin\SecurityMasterDataController;
use App\Http\Controllers\Api\AuctionResultController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\DashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// clear cache
Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    return response()->json([
        'status' => 'success',
        'message' => 'Application cache, config, route, and view caches cleared successfully.'
    ]);
});

Route::prefix('v1')->group(function () {
    // Protected Routes (All routes now protected by Custom Basic Auth)
    // We removed login/logout endpoints as we are using stateless Basic Auth per request.
    
    // Note: ensure_password_changed middleware might be redundant if we are hardcoding the API User
    // but I'll leave it if you still want to enforce that the System User has changed their password.
    // Use 'auth.basic.custom' for checking the hardcoded credentials
    Route::middleware(['auth.basic.custom'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // Benchmarks
        Route::get('/benchmarks', [BenchmarkController::class, 'index']);

        // Securities
        Route::apiResource('securities', SecurityController::class);
        Route::post('/securities/import', [SecurityController::class, 'import']);

        // Auction Results
        Route::get('auction-results/{date}', [AuctionResultController::class, 'getRecordsByDate']);
        Route::apiResource('auction-results', AuctionResultController::class);

        // Security Master Data
        Route::apiResource('security-master-data', SecurityMasterDataController::class);
        
        // Search
        Route::get('/search', [SearchController::class, 'search']);
        
        // Market Categories (Dropdown)
        Route::get('market-categories/all', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'all']);

        // Admin Routes - User Management with Role-Based Access
        Route::prefix('admin')->group(function () {
             
            // User Management
            Route::post('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
            Route::get('users', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
            Route::get('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
            Route::put('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'update']);
            Route::delete('users/{user}', [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
            
            // Pending Users
            Route::get('users/pending', [\App\Http\Controllers\Api\Admin\UserController::class, 'pending']);
            Route::post('users/pending/{pendingUser}/approve', [\App\Http\Controllers\Api\Admin\UserController::class, 'approve']);
            Route::post('users/pending/{pendingUser}/reject', [\App\Http\Controllers\Api\Admin\UserController::class, 'reject']);

            // Market Categories Requests
            Route::post('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'store']);
            Route::put('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'update']);
            Route::delete('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'destroy']);
            
            // Market Categories View
            Route::get('market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'index']);
            Route::get('market-categories/{marketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'show']);

            // Market Categories Approval
            Route::get('pending-market-categories', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'pending']);
            Route::get('pending-market-categories/{pendingMarketCategory}', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'showPending']);
            Route::get('pending-market-categories/{pendingMarketCategory}/compare', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'showPendingWithComparison']);
            Route::post('pending-market-categories/{pendingMarketCategory}/approve', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'approve']);
            Route::post('pending-market-categories/{pendingMarketCategory}/reject', [\App\Http\Controllers\Api\Admin\MarketCategoryController::class, 'reject']);
                
            // Product Type Requests
            Route::post('product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'store']);
            Route::put('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'update']);
            Route::delete('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'destroy']);
             
            // Product Types View
            Route::get('product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'index']);
            Route::get('product-types/{productType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'show']);

            // Product Types Approval
            Route::get('pending-product-types', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'pending']);
            Route::get('pending-product-types/{pendingProductType}', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'showPending']);
            Route::get('pending-product-types/{pendingProductType}/compare', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'showPendingWithComparison']);
            Route::post('pending-product-types/{pendingProductType}/approve', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'approve']);
            Route::post('pending-product-types/{pendingProductType}/reject', [\App\Http\Controllers\Api\Admin\ProductTypeController::class, 'reject']);

            // Security Type Requests
            Route::post('security-types/import', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'import']);
            Route::post('security-types', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'store']);
            Route::put('security-types/{securityType}', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'update']);
            Route::delete('security-types/{securityType}', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'destroy']);
             
            // Security Types View
            Route::get('security-types', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'index']);
            Route::get('security-types/{securityType}', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'show']);

            // Security Types Approval
            Route::get('pending-security-types', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'pending']);
            Route::post('pending-security-types/{pendingSecurityType}/approve', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'approve']);
            Route::post('pending-security-types/{pendingSecurityType}/reject', [\App\Http\Controllers\Api\Admin\SecurityTypeController::class, 'reject']);
          
            // Security Master List Requests
            Route::post('securities/import', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'import']);
            Route::post('securities', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'store']);
            Route::put('securities/{security}', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'update']);
            Route::delete('securities/{security}', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'destroy']);
             
            // Security Master List View
            Route::get('securities', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'index']);
            Route::get('securities/{security}', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'show']);

            // Security Master List Approval
            Route::get('pending-securities', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'pending']);
            Route::post('pending-securities/{pendingSecurity}/approve', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'approve']);
            Route::post('pending-securities/{pendingSecurity}/reject', [\App\Http\Controllers\Api\Admin\SecurityController::class, 'reject']);
          
            // Security Management Fields
            Route::get('security-management', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'index']);
            Route::post('security-management', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'store']);
            Route::post('security-management/direct', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'storeDirect']);
            Route::get('security-management/all', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'getAllFields']);
            Route::get('security-management/{id}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'show']);
            Route::put('security-management/{id}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'update']);
            Route::delete('security-management/{id}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'destroy']);
            Route::get('security-management/category/{categoryId}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'getFieldsByCategory']);
            Route::get('security-management/product-type/{productId}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'getFieldsByProductType']);

            // Security Management Approval
            Route::get('pending-security-management', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'pending']);
            Route::get('pending-security-management/{pendingSecurityManagement}', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'showPending']);
            Route::get('pending-security-management/{pendingSecurityManagement}/compare', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'showPendingWithComparison']);
            Route::post('pending-security-management/{pendingSecurityManagement}/approve', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'approve']);
            Route::post('pending-security-management/{pendingSecurityManagement}/reject', [\App\Http\Controllers\Api\Admin\SecurityManagementController::class, 'reject']);

            // Security Master Data
            Route::post('security-master/bulk-upload', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'bulkUpload']);
            Route::get('security-master', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'index']);
            Route::post('security-master', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'store']);
            Route::get('security-master/{id}', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'show']);
            Route::put('security-master/{id}', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'update']);
            Route::delete('security-master/{id}', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'destroy']);
            Route::post('security-master/{id}/call-schedules/{callId}/exercise', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'exerciseCall']);
            Route::post('security-master/{id}/reset-schedules/{resetId}/apply', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'applyReset']);
            
            // Security Master Data Approval
            Route::get('pending-security-master', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'pending']);
            Route::get('pending-security-master/{pendingSecurity}', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'showPending']);
            Route::get('pending-security-master/{pendingSecurity}/compare', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'showPendingWithComparison']);
            Route::post('pending-security-master/{pendingSecurity}/approve', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'approve']);
            Route::post('pending-security-master/{pendingSecurity}/reject', [\App\Http\Controllers\Api\Admin\SecurityMasterDataController::class, 'reject']);
          
               // Auction Results
            Route::apiResource('auction-results', AuctionResultController::class);
        

            // Auction Results (Admin)
            Route::post('auction-results/bulk-upload', [\App\Http\Controllers\Api\AuctionResultController::class, 'bulkUpload']);
            
            // Auction Results Approval
            Route::get('pending-auction-results', [\App\Http\Controllers\Api\AuctionResultController::class, 'pending']);
            Route::get('pending-auction-results/{pendingAuctionResult}', [\App\Http\Controllers\Api\AuctionResultController::class, 'showPending']);
            Route::post('pending-auction-results/{pendingAuctionResult}/approve', [\App\Http\Controllers\Api\AuctionResultController::class, 'approve']);
            Route::post('pending-auction-results/{pendingAuctionResult}/reject', [\App\Http\Controllers\Api\AuctionResultController::class, 'reject']);

            // Bond Features & Configurations
            Route::apiResource('coupon-types', \App\Http\Controllers\Api\Admin\CouponTypeController::class);
            Route::apiResource('day-count-conventions', \App\Http\Controllers\Api\Admin\DayCountConventionController::class);
            Route::apiResource('coupon-frequencies', \App\Http\Controllers\Api\Admin\CouponFrequencyController::class);
            Route::apiResource('option-types', \App\Http\Controllers\Api\Admin\OptionTypeController::class);
            Route::apiResource('listing-statuses', \App\Http\Controllers\Api\Admin\ListingStatusController::class);
            Route::apiResource('trading-statuses', \App\Http\Controllers\Api\Admin\TradingStatusController::class);
          
            // Test endpoint for debugging external user service
            Route::get('test-users', [\App\Http\Controllers\Api\Admin\TestUserServiceController::class, 'testUsers']);
          
        });
    });
});
