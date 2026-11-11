@extends('layouts.app')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">System Audit Logs</h1>
        <span class="badge bg-info text-dark">{{ $activities->total() }} Records</span>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm align-middle mb-0" style="font-size: 0.9rem;">
                    <thead class="bg-light">
                        <tr>
                            <th>User</th>
                            <th>Description</th>
                            <th>Subject Type</th>
                            <th>Subject ID</th>
                            <th>Date & Time</th>
                            <th>Properties</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activities as $activity)
                        <tr>
                            <td>
                                @if($activity->causer)
                                    <span class="fw-bold">{{ $activity->causer->full_name }}</span>
                                @else
                                    <span class="text-muted fst-italic">System</span>
                                @endif
                            </td>
                            <td>{{ ucfirst($activity->description) }}</td>
                            <td>{{ class_basename($activity->subject_type) }}</td>
                            <td>{{ $activity->subject_id }}</td>
                            <td>{{ $activity->created_at->format('d M Y, H:i:s') }}</td>
                            <td>
                                @if($activity->properties && $activity->properties->count() > 0)
                                    <button class="btn btn-xs btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#log-prop-{{ $activity->id }}">
                                        View Data
                                    </button>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                        @if($activity->properties && $activity->properties->count() > 0)
                        <tr class="collapse bg-light" id="log-prop-{{ $activity->id }}">
                            <td colspan="6" class="p-3">
                                <pre class="small mb-0" style="max-height: 200px; overflow-y: auto;">{{ json_encode($activity->properties, JSON_PRETTY_PRINT) }}</pre>
                            </td>
                        </tr>
                        @endif
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">No logs found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($activities->hasPages())
        <div class="card-footer bg-white">
            {{ $activities->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
