<?php

namespace App\Http\Controllers;

use App\Models\AuctionResult;
use App\Models\Security;
use App\Models\PendingAction;
use App\Http\Requests\StoreAuctionResultRequest;
use App\Http\Requests\UpdateAuctionResultRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class AuctionResultController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = AuctionResult::with('security');

        if ($request->filled('security_id')) {
            $query->where('security_id', $request->security_id);
        }
        
        if ($request->filled('auction_date')) {
            $query->whereDate('auction_date', $request->auction_date);
        }

        $auctionResults = $query->latest('auction_date')->paginate(15)->withQueryString();

        return view('auction_results.index', compact('auctionResults'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $securities = Security::latest()->get();
        return view('auction_results.create', [
            'auctionResult' => new AuctionResult(),
            'securities' => $securities
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAuctionResultRequest $request)
    {
        $data = $request->validated();
        
        // Auto-calculate total amount sold
        $data['non_competitive_sales'] = $data['non_competitive_sales'] ?? 0;
        $data['total_amount_sold'] = $data['amount_sold'] + $data['non_competitive_sales'];
        
        // Fill model to use calculation helper
        $auctionResult = new AuctionResult($data);
        $auctionResult->calculateRatios();
        
        // Day
        $auctionResult->day_of_week = \Carbon\Carbon::parse($data['auction_date'])->format('l');

        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            // Need to handle model attributes that were set separately like day_of_week
            $data['day_of_week'] = $auctionResult->day_of_week;
            $data['total_amount_sold'] = $auctionResult->total_amount_sold;
            // Also need bid_cover_ratio etc if calculated. 
            // Better: use $auctionResult->toArray() but remove nulls?
            // Actually, fillable protection matters. PendingAction uses specific data.
            // Let's rely on the $data array + calculated fields.
            $data['bid_cover_ratio'] = $auctionResult->bid_cover_ratio;
            $data['subscription_level'] = $auctionResult->subscription_level;

            PendingAction::create([
                'action_type' => 'create',
                'model_type' => AuctionResult::class,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('auction-results.index')
                ->with('success', 'Auction Result recording submitted for approval.');
        }
        
        $auctionResult->created_by = Auth::id();
        $auctionResult->save();

        return redirect()->route('auction-results.show', $auctionResult)
            ->with('success', 'Auction Result created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(AuctionResult $auctionResult)
    {
        $auctionResult->load('security', 'creator');
        return view('auction_results.show', compact('auctionResult'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(AuctionResult $auctionResult)
    {
        $securities = Security::latest()->get();
        return view('auction_results.edit', compact('auctionResult', 'securities'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAuctionResultRequest $request, AuctionResult $auctionResult)
    {
        $data = $request->validated();
        
        $data['non_competitive_sales'] = $data['non_competitive_sales'] ?? 0;
        $data['total_amount_sold'] = $data['amount_sold'] + $data['non_competitive_sales'];
        
        $auctionResult->fill($data);
        $auctionResult->calculateRatios();
        $auctionResult->day_of_week = \Carbon\Carbon::parse($data['auction_date'])->format('l');
        
        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
             $data['day_of_week'] = $auctionResult->day_of_week;
             $data['total_amount_sold'] = $auctionResult->total_amount_sold;
             $data['bid_cover_ratio'] = $auctionResult->bid_cover_ratio;
             $data['subscription_level'] = $auctionResult->subscription_level;

            PendingAction::create([
                'action_type' => 'update',
                'model_type' => AuctionResult::class,
                'model_id' => $auctionResult->id,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('auction-results.index')
                ->with('success', 'Auction Result update submitted for approval.');
        }

        $auctionResult->updated_by = Auth::id();
        
        $auctionResult->save();

        return redirect()->route('auction-results.show', $auctionResult)
            ->with('success', 'Auction Result updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuctionResult $auctionResult)
    {
        $auctionResult->delete();
        return redirect()->route('auction-results.index')
            ->with('success', 'Auction Result deleted successfully.');
    }

    public function exportExcel()
    {
        return Excel::download(new \App\Exports\AuctionResultsExport, 'auction-results-' . date('Y-m-d') . '.xlsx');
    }

    public function exportPdf()
    {
        $auctionResults = AuctionResult::with('security')->latest('auction_date')->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('auction_results.pdf', compact('auctionResults'));
        $pdf->setPaper('a4', 'landscape');
        return $pdf->download('auction-results-' . date('Y-m-d') . '.pdf');
    }
}
