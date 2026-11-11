<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecurityRequest;
use App\Http\Requests\UpdateSecurityRequest;
use App\Models\Security;
use App\Models\ProductType;
use App\Models\PendingAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SecuritiesExport;
use App\Imports\SecuritiesImport;
use PDF; // Barryvdh\DomPDF\Facade\Pdf

class SecurityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Security::with(['productType', 'creator']);

        // Filters
        if ($request->filled('product_type')) {
            $query->where('product_type_id', $request->product_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('issuer')) {
            $query->where('issuer', 'like', '%' . $request->issuer . '%');
        }

        // Sort by latest by default
        $query->latest();

        // Pagination is handled in the view via standard Laravel methods for now
        // If DataTables AJAX is requested later, we can check $request->ajax() here.
        if ($request->ajax()) {
             return datatables()->of($query)->toJson();
        }

        $securities = $query->paginate(15)->withQueryString();

        return view('securities.index', compact('securities'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('securities.create', ['security' => new Security()]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSecurityRequest $request)
    {
        $data = $request->validated();

        // MAKER-CHECKER: If not Super Admin, create Pending Action
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'create',
                'model_type' => Security::class,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('securities.index')
                ->with('success', 'Security creation submitted for approval.');
        }

        // Direct Save (Super Admin)
        $data['created_by'] = Auth::id();
        $security = Security::create($data);

        return redirect()->route('securities.show', $security)
            ->with('success', 'Security created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Security $security)
    {
        return view('securities.show', compact('security'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Security $security)
    {
        return view('securities.edit', compact('security'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSecurityRequest $request, Security $security)
    {
        $data = $request->validated();

        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'update',
                'model_type' => Security::class,
                'model_id' => $security->id,
                'data' => $data,
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('securities.index')
                ->with('success', 'Security update submitted for approval.');
        }

        $security->update($data);

        return redirect()->route('securities.show', $security)
            ->with('success', 'Security updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Security $security)
    {
        // MAKER-CHECKER
        if (!Auth::user()->hasRole('super_admin')) {
            PendingAction::create([
                'action_type' => 'delete',
                'model_type' => Security::class,
                'model_id' => $security->id,
                'data' => [],
                'status' => 'pending',
                'created_by' => Auth::id(),
            ]);

            return redirect()->route('securities.index')
                ->with('success', 'Security deletion submitted for approval.');
        }

        $security->delete();
        return redirect()->route('securities.index')
            ->with('success', 'Security deleted successfully.');
    }

    /**
     * Export to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new SecuritiesExport, 'securities_' . date('Y-m-d') . '.xlsx');
    }

    /**
     * Export to PDF
     */
    public function exportPdf()
    {
        // Using dompdf wrapper
        $securities = Security::with('productType')->latest()->get();
        
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('securities.pdf', compact('securities'));
        $pdf->setPaper('a4', 'landscape');
        
        return $pdf->download('security-master-list-' . date('Y-m-d') . '.pdf');
    }

    /**
     * Import from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        Excel::import(new SecuritiesImport, $request->file('file'));

        return back()->with('success', 'Securities imported successfully.');
    }
}
