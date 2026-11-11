@extends('layouts.app')

@section('title', 'Review Action')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 text-gray-800">Review Pending Action</h1>
            <p class="text-muted mb-0">
                Initiated by <strong>{{ $pendingAction->creator->full_name }}</strong> on {{ $pendingAction->created_at->format('d M Y, H:i') }}
            </p>
        </div>
        <a href="{{ route('authoriser.pending-approvals') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>

    <!-- Data Comparison -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
             <div class="d-flex justify-content-between">
                <h6 class="m-0 fw-bold text-primary">
                    {{ ucfirst($pendingAction->action_type) }} {{ class_basename($pendingAction->model_type) }}
                </h6>
                <span class="badge bg-{{ $pendingAction->action_type === 'delete' ? 'danger' : 'success' }}">
                    {{ strtoupper($pendingAction->action_type) }}
                </span>
            </div>
        </div>
        <div class="card-body">
            @if($pendingAction->action_type === 'delete')
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Are you sure you want to approve the deletion of this record?
                </div>
            @endif

            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th style="width: 30%;">Field</th>
                        @if($pendingAction->action_type === 'update')
                        <th style="width: 35%;">Current Value</th>
                        @endif
                        <th style="width: 35%;">{{ $pendingAction->action_type === 'update' ? 'New Value' : 'Data' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Merge keys from both old and new to show all fields
                        $allKeys = array_unique(array_merge(array_keys($oldData), array_keys($newData)));
                        // Filter out internal fields
                        $ignored = ['id', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by'];
                        $allKeys = array_diff($allKeys, $ignored);
                    @endphp

                    @foreach($allKeys as $key)
                        @php
                            $oldVal = $oldData[$key] ?? null;
                            $newVal = $newData[$key] ?? null;
                            
                            // Highlight if changed (for update)
                            $changed = ($pendingAction->action_type === 'update' && $oldVal != $newVal);
                            
                            // Helper to format array/objects
                            $format = fn($v) => is_array($v) ? json_encode($v) : $v;
                        @endphp
                        
                        @if($pendingAction->action_type === 'create' || $changed || $pendingAction->action_type === 'delete')
                        <tr class="{{ $changed ? 'table-warning' : '' }}">
                            <td class="fw-bold">{{ ucwords(str_replace('_', ' ', $key)) }}</td>
                            @if($pendingAction->action_type === 'update')
                            <td class="text-muted">{{ $format($oldVal) }}</td>
                            @endif
                            <td class="{{ $changed ? 'fw-bold text-dark' : '' }}">
                                {{ $format($newVal ?? $oldVal) }} 
                                <!-- For delete, we show oldVal effectively as "Data" but logic above uses columns.
                                     Actually for delete, newData is empty usually. We should just show Old Data. -->
                                @if($pendingAction->action_type === 'delete')
                                    {{ $format($oldVal) }}
                                @endif
                            </td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Actions -->
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title">Decision</h5>
            <div class="row">
                <div class="col-md-6">
                    <form action="{{ route('authoriser.approve', $pendingAction) }}" method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this action?');">
                        @csrf
                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="bi bi-check-circle me-2"></i>Approve & Execute
                            </button>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <button type="button" class="btn btn-danger btn-lg w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
                        <i class="bi bi-x-circle me-2"></i>Reject
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reject Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('authoriser.reject', $pendingAction) }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject Action</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
