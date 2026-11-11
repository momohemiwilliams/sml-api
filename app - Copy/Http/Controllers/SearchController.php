<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Security;
use App\Models\AuctionResult;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->input('q');
        
        if (!$query) {
            return view('search.results', [
                'securities' => collect(), 
                'auctions' => collect(), 
                'query' => null
            ]);
        }
        
        if (strlen($query) < 2) {
             return redirect()->back()->with('warning', 'Please enter at least 2 characters to search.');
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

        return view('search.results', compact('securities', 'auctions', 'query'));
    }

    public function advanced(Request $request)
    {
        $hasSearch = $request->anyFilled(['keyword', 'product_type_id', 'date_from', 'date_to', 'status']);
        $results = collect();

        if ($hasSearch) {
             $query = Security::query()->with('productType');
             
             if ($request->filled('keyword')) {
                 $k = $request->input('keyword');
                 $query->where(function($q) use ($k) {
                     $q->where('security_name', 'like', "%{$k}%")
                       ->orWhere('isin', 'like', "%{$k}%")
                       ->orWhere('issuer', 'like', "%{$k}%");
                 });
             }
             
             if ($request->filled('product_type_id')) {
                 $query->where('product_type_id', $request->input('product_type_id'));
             }
             
             if ($request->filled('date_from')) {
                 $query->whereDate('issue_date', '>=', $request->input('date_from'));
             }
             
             if ($request->filled('date_to')) {
                 $query->whereDate('issue_date', '<=', $request->input('date_to'));
             }
             
             if ($request->filled('status')) {
                 $query->where('status', $request->input('status'));
             }
             
             $results = $query->latest('issue_date')->paginate(20)->withQueryString();
        }

        return view('search.advanced', [
            'results' => $results,
            'productTypes' => \App\Models\ProductType::all(),
            'hasSearch' => $hasSearch
        ]);
    }
}
