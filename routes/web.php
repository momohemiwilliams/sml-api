<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\AuctionResultController;
use App\Http\Controllers\Authoriser\PendingActionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProductTypeController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', function () {
    return redirect('/login');
});

// Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    // Common Dashboard (fallback)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Super Admin Routes
    Route::middleware('role:super_admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', function () {
            return view('admin.dashboard'); // Or dedicated AdminDashboardController if needed
        })->name('dashboard');
        
        // User Management
        Route::resource('users', UserController::class);
        
        // Product Types
        Route::resource('product-types', ProductTypeController::class)->except(['create', 'edit', 'show']);
        
        // System Settings
        Route::get('/settings', function () {
            return view('admin.settings');
        })->name('settings');
        
        // Audit Logs
        Route::get('/audit-logs', [\App\Http\Controllers\Admin\AuditLogController::class, 'index'])->name('audit-logs');
    });
    
    // Inputter Routes
    Route::middleware('role:inputter|super_admin')->prefix('inputter')->name('inputter.')->group(function () {
        Route::get('/dashboard', function () {
            return view('inputter.dashboard');
        })->name('dashboard');
        
        // Securities Management (Inputter can create/edit)
        // inputter.securities.index, create, store, show, edit, update
        Route::resource('securities', SecurityController::class)->except(['destroy']);
        
        // Auction Results Management
        Route::resource('auction-results', AuctionResultController::class)->except(['destroy']);
        
        // My Submissions
        Route::get('/my-submissions', function () {
            return view('inputter.submissions');
        })->name('submissions');
    });
    
    // Authoriser Routes
    Route::middleware('role:authoriser|super_admin')->prefix('authoriser')->group(function () {
        Route::get('/dashboard', function () {
            return view('authoriser.dashboard');
        })->name('authoriser.dashboard');
        
        // Pending Approvals
        Route::get('/pending-approvals', [PendingActionController::class, 'index'])->name('authoriser.pending-approvals');
        Route::get('/pending-approvals/{pendingAction}', [PendingActionController::class, 'show'])->name('authoriser.pending-show');
        
        // Approve/Reject Actions
        Route::post('/pending-approvals/{pendingAction}/approve', [PendingActionController::class, 'approve'])->name('authoriser.approve');
        Route::post('/pending-approvals/{pendingAction}/reject', [PendingActionController::class, 'reject'])->name('authoriser.reject');
    });
    
    // Shared Routes (All authenticated users)
    Route::middleware('permission:view-securities')->group(function () {
        // View Securities (Read-only for all)
        Route::get('/securities', [SecurityController::class, 'index'])->name('securities.index');
        Route::get('/securities/{security}', [SecurityController::class, 'show'])->name('securities.show');
        
        // Export
        Route::get('/securities/export/excel', [SecurityController::class, 'exportExcel'])->name('securities.export.excel');
        Route::get('/securities/export/pdf', [SecurityController::class, 'exportPdf'])->name('securities.export.pdf');

        // View Auction Results (Read-only for all)
        Route::get('/auction-results', [AuctionResultController::class, 'index'])->name('auction-results.index');
        Route::get('/auction-results/{auctionResult}', [AuctionResultController::class, 'show'])->name('auction-results.show');
        
        // Global Search
        Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
        Route::get('/search/advanced', [\App\Http\Controllers\SearchController::class, 'advanced'])->name('search.advanced');
        
        // Export Auction Results
        Route::get('/auction-results/export/excel', [AuctionResultController::class, 'exportExcel'])->name('auction-results.export.excel');
        Route::get('/auction-results/export/pdf', [AuctionResultController::class, 'exportPdf'])->name('auction-results.export.pdf');
    });
    
    // Import (Inputter and Super Admin only)
    Route::middleware('permission:create-securities')->group(function () {
        Route::post('/securities/import', [SecurityController::class, 'import'])->name('securities.import');
    });
    
    // API Routes for AJAX
    Route::prefix('api')->group(function () {
        // Get product types by market category
        Route::get('/product-types/{marketCategoryId}', function ($marketCategoryId) {
            return \App\Models\ProductType::where('market_category_id', $marketCategoryId)
                ->where('is_active', true)
                ->get();
        })->name('api.product-types');
        
        // Calculate tenor
        Route::post('/calculate-tenor', function (\Illuminate\Http\Request $request) {
            $issueDate = \Carbon\Carbon::parse($request->issue_date);
            $maturityDate = \Carbon\Carbon::parse($request->maturity_date);
            return response()->json(['tenor' => $maturityDate->diffInYears($issueDate)]);
        })->name('api.calculate-tenor');
    });
});
