@extends('layouts.app')

@section('title', 'Pending Approvals')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Pending Approvals</h1>
        <span class="badge bg-warning text-dark">{{ $pendingActions->total() }} Pending</span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Initiated By</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Target ID</th>
                            <th>Date Initiated</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingActions as $action)
                        <tr>
                            <td>
                                <div class="fw-bold">{{ $action->creator->full_name ?? 'Unknown' }}</div>
                                <small class="text-muted">{{ $action->creator->email ?? '-' }}</small>
                            </td>
                            <td>
                                @if($action->action_type === 'create')
                                    <span class="badge bg-success">Create</span>
                                @elseif($action->action_type === 'update')
                                    <span class="badge bg-primary">Update</span>
                                @elseif($action->action_type === 'delete')
                                    <span class="badge bg-danger">Delete</span>
                                @endif
                            </td>
                            <td>{{ class_basename($action->model_type) }}</td>
                            <td>{{ $action->model_id ?? 'New Record' }}</td>
                            <td>{{ $action->created_at->format('d M Y, H:i') }}</td>
                            <td>
                                <a href="{{ route('authoriser.pending-show', $action) }}" class="btn btn-sm btn-primary">
                                    <i class="bi bi-eye me-1"></i>Review
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-check-circle fs-1 d-block mb-3 text-success"></i>
                                No pending approvals found. All caught up!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($pendingActions->hasPages())
        <div class="card-footer bg-white">
            {{ $pendingActions->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
