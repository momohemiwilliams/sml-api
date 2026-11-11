<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Security;
use App\Models\AuctionResult;
use App\Models\ProductType;

class SearchController extends Controller
{
    /**
     * Handle search requests (Simple & Advanced).
     */
    public function search(Request $request)
    {
        // 1. Simple Keyword Search
        if ($request->filled('q') && !$request->anyFilled(['product_type_id', 'date_from', 'date_to', 'status'])) {
            $query = $request->input('q');
            
            if (strlen($query) < 2) {
                 return response()->json([
                     'message' => 'Please enter at least 2 characters to search.'
                 ], 400); // Bad Request
            }

            $securities = Security::where('security_name', 'like', "%{$query}%")
                            ->orWhere('isin', 'like', "%{$query}%")
                            ->orWhere('issuer', 'like', "%{$query}%")
                            ->latest()
                            ->limit(20)->get();

            $auctions = AuctionResult::where('auction_number', 'like', "%{$query}%")
                            ->orWhereHas('security', function($q) use ($query) {
                                $q->where('security_name', 'like', "%{$query}%")
                                  ->orWhere('isin', 'like', "%{$query}%");
                            })
                            ->with('security')
                            ->latest('auction_date')
                            ->limit(20)->get();

            return response()->json([
                'type' => 'simple',
                'securities' => $securities,
                'auctions' => $auctions
            ]);
        }

        // 2. Advanced Search (Securities only for now, as per original controller logic seems focused on securities for advanced)
        // Original 'advanced' method focused on Securities.
        
        $query = Security::query()->with('productType');
        $hasFilters = false;

        if ($request->filled('keyword')) {
            $hasFilters = true;
            $k = $request->input('keyword');
            $query->where(function($q) use ($k) {
                $q->where('security_name', 'like', "%{$k}%")
                  ->orWhere('isin', 'like', "%{$k}%")
                  ->orWhere('issuer', 'like', "%{$k}%");
            });
        }
        
        if ($request->filled('product_type_id')) {
            $hasFilters = true;
            $query->where('product_type_id', $request->input('product_type_id'));
        }
        
        if ($request->filled('date_from')) {
            $hasFilters = true;
            $query->whereDate('issue_date', '>=', $request->input('date_from'));
        }
        
        if ($request->filled('date_to')) {
            $hasFilters = true;
            $query->whereDate('issue_date', '<=', $request->input('date_to'));
        }
        
        if ($request->filled('status')) {
            $hasFilters = true;
            $query->where('status', $request->input('status'));
        }

        if ($hasFilters) {
            $results = $query->latest('issue_date')->paginate(20)->withQueryString();
            return response()->json([
                'type' => 'advanced',
                'results' => $results
            ]);
        }
        
        // If no filters provided
        return response()->json([
            'message' => 'No search criteria provided.',
            'product_types' => ProductType::select('id', 'name')->get() // Helper data
        ]);
    }
}
