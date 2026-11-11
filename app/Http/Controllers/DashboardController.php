<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Security;
use App\Models\AuctionResult;
use App\Models\PendingAction;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. Headline Stats
        $totalSecurities = Security::where('status', 'Active')->count();
        $totalAuctions = AuctionResult::count();
        // Pending approvals helpful for Authorisers primarily, but good for visibility
        $pendingApprovals = PendingAction::where('status', 'pending')->count();
        
        // 2. Portfolio Mix (Pie Chart Data)
        // Group Active securities by Product Type
        $portfolioData = Security::where('status', 'Active')
            ->get()
            ->groupBy(function($item) {
                return $item->productType->name ?? 'Other';
            })
            ->map(function($group) {
                return $group->count();
            });
            
        $portfolioLabels = $portfolioData->keys();
        $portfolioValues = $portfolioData->values();

        // 3. Auction Trend (Bar Chart Data)
        // Sum of total_amount_sold by Auction Date (grouped by month or just last 5 auctions)
        // Let's do last 5 auctions for simplicity and clarity on specific events
        $recentAuctions = AuctionResult::latest('auction_date')
            ->take(5)
            ->get()
            ->reverse(); // Chronological order
            
        $auctionLabels = $recentAuctions->map(fn($a) => $a->auction_number); // Or date
        $auctionValues = $recentAuctions->map(fn($a) => $a->total_amount_sold / 1000000000); // In Billions

        // 4. Recent Activity
        $recentActivities = \Spatie\Activitylog\Models\Activity::latest()
            ->with('causer')
            ->limit(10)
            ->get();

        return view('dashboard', compact(
            'totalSecurities', 
            'totalAuctions', 
            'pendingApprovals',
            'portfolioLabels',
            'portfolioValues',
            'auctionLabels',
            'auctionValues',
            'recentActivities'
        ));
    }
}
