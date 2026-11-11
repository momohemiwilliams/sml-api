<?php

namespace App\Http\Controllers\Authoriser;

use App\Http\Controllers\Controller;
use App\Models\PendingAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PendingActionController extends Controller
{
    public function index()
    {
        $pendingActions = PendingAction::with('creator')
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        return view('authoriser.pending_actions.index', compact('pendingActions'));
    }

    public function show(PendingAction $pendingAction)
    {
        $newData = $pendingAction->data ?? [];
        $oldData = [];
        $targetModel = null;
        
        if ($pendingAction->model_id && class_exists($pendingAction->model_type)) {
             $targetModel = $pendingAction->model_type::withTrashed()->find($pendingAction->model_id);
             if ($targetModel) {
                 $oldData = $targetModel->toArray();
             }
        }
        
        return view('authoriser.pending_actions.show', compact('pendingAction', 'newData', 'oldData', 'targetModel'));
    }

    public function approve(PendingAction $pendingAction)
    {
        if ($pendingAction->status !== 'pending') {
            return back()->with('error', 'This action has already been processed.');
        }

        $data = $pendingAction->data;
        $modelClass = $pendingAction->model_type;

        try {
            if ($pendingAction->action_type === 'create') {
                $data['created_by'] = $pendingAction->created_by;
                // Add approval metadata if model supports it (optional)
                
                $model = $modelClass::create($data);
                
            } elseif ($pendingAction->action_type === 'update') {
                $model = $modelClass::withTrashed()->find($pendingAction->model_id);
                if ($model) {
                    $model->update($data);
                } else {
                    return back()->with('error', 'Target record not found.');
                }
                
            } elseif ($pendingAction->action_type === 'delete') {
                $model = $modelClass::withTrashed()->find($pendingAction->model_id);
                if ($model) {
                    $model->delete();
                }
            }

            $pendingAction->update([
                'status' => 'approved',
                'approved_by' => Auth::id(),
                'approved_at' => now(),
            ]);

            return redirect()->route('authoriser.pending-approvals')
                ->with('success', 'Action approved and executed successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error executing action: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, PendingAction $pendingAction)
    {
        if ($pendingAction->status !== 'pending') {
            return back()->with('error', 'This action has already been processed.');
        }

        $pendingAction->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => now(),
            'remarks' => $request->input('remarks'),
        ]);

        return redirect()->route('authoriser.pending-approvals')
            ->with('success', 'Action rejected.');
    }
}
