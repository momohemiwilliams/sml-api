@extends('layouts.app')

@section('title', 'Auction Results')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Auction Results</h1>
        @can('role:inputter|super_admin')
            <a href="{{ route('inputter.auction-results.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Record New Result
            </a>
        @endcan
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('auction-results.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label visually-hidden">Security</label>
                    <select name="security_id" class="form-select">
                        <option value="">Filter by Security</option>
                        @foreach(\App\Models\Security::all() as $sec)
                            <option value="{{ $sec->id }}" {{ request('security_id') == $sec->id ? 'selected' : '' }}>
                                {{ $sec->security_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label visually-hidden">Auction Date</label>
                    <input type="date" name="auction_date" class="form-control" value="{{ request('auction_date') }}">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-secondary w-100">Filter</button>
                </div>
                @if(request()->anyFilled(['security_id', 'auction_date']))
                <div class="col-md-2">
                    <a href="{{ route('auction-results.index') }}" class="btn btn-outline-secondary w-100">Clear</a>
                </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="mb-3 d-flex gap-2">
        <a href="{{ route('auction-results.export.excel') }}" class="btn btn-success">
            <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
        </a>
        <a href="{{ route('auction-results.export.pdf') }}" class="btn btn-danger">
            <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
        </a>
    </div>

    <!-- Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Auction No.</th>
                            <th>Security</th>
                            <th>Date</th>
                            <th>Tenor</th>
                            <th class="text-end">Offered</th>
                            <th class="text-end">Sold</th>
                            <th class="text-end">Stop Rate</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($auctionResults as $result)
                            <tr>
                                <td class="fw-bold">
                                    <a href="{{ route('auction-results.show', $result) }}" class="text-decoration-none">
                                        {{ $result->auction_number }}
                                    </a>
                                </td>
                                <td>
                                    <div>{{ $result->security->security_name }}</div>
                                    <small class="text-muted">{{ $result->security->isin }}</small>
                                </td>
                                <td>{{ $result->auction_date->format('d M Y') }}</td>
                                <td>{{ $result->tenor_days }} Days</td>
                                <td class="text-end">{{ number_format($result->amount_offered, 2) }}</td>
                                <td class="text-end">{{ number_format($result->amount_sold, 2) }}</td>
                                <td class="text-end">{{ $result->stop_rate }}%</td>
                                <td>
                                    <span class="badge bg-{{ $result->status === 'Completed' ? 'success' : 'warning' }}">
                                        {{ $result->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('auction-results.show', $result) }}" class="btn btn-outline-primary" title="View">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        @can('role:inputter|super_admin')
                                        <a href="{{ route('inputter.auction-results.edit', $result) }}" class="btn btn-outline-secondary" title="Edit">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">No auction results found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($auctionResults->hasPages())
        <div class="card-footer bg-white">
            {{ $auctionResults->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
