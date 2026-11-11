@extends('layouts.app')

@section('title', 'Advanced Search')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 text-gray-800">Advanced Search</h1>
    </div>

    <!-- Search Form -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h6 class="m-0 fw-bold text-primary"><i class="bi bi-funnel me-1"></i>Search Filters</h6>
        </div>
        <div class="card-body">
            <form action="{{ route('search.advanced') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Keyword</label>
                        <input type="text" name="keyword" class="form-control" placeholder="ISIN, Name, or Issuer" value="{{ request('keyword') }}">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Product Type</label>
                        <select name="product_type_id" class="form-select">
                            <option value="">All Types</option>
                            @foreach($productTypes as $type)
                                <option value="{{ $type->id }}" {{ request('product_type_id') == $type->id ? 'selected' : '' }}>
                                    {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Matured" {{ request('status') == 'Matured' ? 'selected' : '' }}>Matured</option>
                            <option value="Cancelled" {{ request('status') == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                         <label class="form-label">Issue Date From</label>
                         <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                    </div>
                    
                     <div class="col-md-3">
                         <label class="form-label">Issue Date To</label>
                         <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                    </div>
                    
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2 flex-grow-1">
                            <i class="bi bi-search me-1"></i>Search
                        </button>
                        <a href="{{ route('search.advanced') }}" class="btn btn-outline-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    @if($hasSearch)
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-gray-800">Search Results ({{ $results instanceof \Illuminate\Pagination\LengthAwarePaginator ? $results->total() : $results->count() }})</h6>
            </div>
            <div class="card-body p-0">
                @if($results->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th>Security Name</th>
                                <th>ISIN</th>
                                <th>Product Type</th>
                                <th>Issue Date</th>
                                <th>Maturity</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $sec)
                            <tr>
                                <td class="fw-bold">{{ $sec->security_name }}</td>
                                <td>{{ $sec->isin }}</td>
                                <td>{{ $sec->productType->name ?? '-' }}</td>
                                <td>{{ $sec->issue_date->format('d M Y') }}</td>
                                <td>{{ $sec->maturity_date->format('d M Y') }}</td>
                                <td>
                                    <span class="badge bg-{{ $sec->status === 'Active' ? 'success' : ($sec->status === 'Matured' ? 'secondary' : 'warning') }}">
                                        {{ $sec->status }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('securities.show', $sec) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if($results instanceof \Illuminate\Pagination\LengthAwarePaginator)
                <div class="px-3 py-3">
                    {{ $results->links() }}
                </div>
                @endif
                @else
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-search fs-1 d-block mb-3"></i>
                    No securities found matching your filters.
                </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-light border text-center py-5">
            Use the filters above to search for securities.
        </div>
    @endif
</div>
@endsection
