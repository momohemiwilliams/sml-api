<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        $pendingApprovals = PendingAction::where('status', 'pending')->count();
        
        // 2. Portfolio Mix (Pie Chart Data)
        $portfolioData = Security::where('status', 'Active')
            ->get()
            ->groupBy(function($item) {
                return $item->productType->name ?? 'Other';
            })
            ->map(function($group) {
                return $group->count();
            });
            
        // 3. Auction Trend (Bar Chart Data)
        $recentAuctions = AuctionResult::latest('auction_date')
            ->take(5)
            ->get()
            ->reverse()
            ->values(); // Reset keys for JSON array
            
        $auctionTrend = $recentAuctions->map(function($a) {
            return [
                'auction_number' => $a->auction_number,
                'total_amount_sold' => $a->total_amount_sold / 1000000000,
                'date' => $a->auction_date
            ];
        });

        // 4. Recent Activity
        $recentActivities = \Spatie\Activitylog\Models\Activity::latest()
            ->with('causer')
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => [
                'total_securities' => $totalSecurities,
                'total_auctions' => $totalAuctions,
                'pending_approvals' => $pendingApprovals
            ],
            'portfolio_mix' => $portfolioData,
            'auction_trend' => $auctionTrend,
            'recent_activity' => $recentActivities
        ]);
    }
}
