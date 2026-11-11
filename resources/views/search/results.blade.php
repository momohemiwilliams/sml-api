@extends('layouts.app')

@section('title', 'Search Results')

@section('content')
<div class="container-fluid">
    <div class="mb-4">
        <h1 class="h3 text-gray-800">Search Results</h1>
        <p class="text-muted">
            Showing results for: <strong>"{{ $query }}"</strong>
            <a href="{{ route('search.advanced') }}" class="ms-3 text-decoration-none"><i class="bi bi-sliders me-1"></i>Advanced Search</a>
        </p>
    </div>

    @if($securities->isEmpty() && $auctions->isEmpty())
        <div class="alert alert-info py-4 text-center">
            <i class="bi bi-search fs-1 text-muted d-block mb-3"></i>
            <h5>No matches found</h5>
            <p class="mb-0">Try adjusting your search terms or filters.</p>
        </div>
    @else
        <ul class="nav nav-tabs mb-4" id="searchTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $securities->isNotEmpty() ? 'active' : '' }}" id="securities-tab" data-bs-toggle="tab" data-bs-target="#securities" type="button" role="tab">
                    Securities <span class="badge bg-secondary ms-2">{{ $securities->count() }}</span>
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $securities->isEmpty() && $auctions->isNotEmpty() ? 'active' : '' }}" id="auctions-tab" data-bs-toggle="tab" data-bs-target="#auctions" type="button" role="tab">
                    Auction Results <span class="badge bg-secondary ms-2">{{ $auctions->count() }}</span>
                </button>
            </li>
        </ul>

        <div class="tab-content" id="searchTabsContent">
            <!-- Securities Tab -->
            <div class="tab-pane fade {{ $securities->isNotEmpty() ? 'show active' : '' }}" id="securities" role="tabpanel">
                @if($securities->isNotEmpty())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Security Name</th>
                                            <th>ISIN</th>
                                            <th>Issuer</th>
                                            <th>Asset Class</th>
                                            <th>Maturity</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($securities as $sec)
                                        <tr>
                                            <td class="fw-bold">{{ $sec->security_name }}</td>
                                            <td>{{ $sec->isin }}</td>
                                            <td>{{ $sec->issuer }}</td>
                                            <td>{{ $sec->productType->name ?? '-' }}</td>
                                            <td>{{ $sec->maturity_date->format('d M Y') }}</td>
                                            <td>
                                                <span class="badge bg-{{ $sec->status === 'Active' ? 'success' : 'secondary' }}">
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
                        </div>
                    </div>
                @else
                    <div class="text-muted p-3">No securities found matching your criteria.</div>
                @endif
            </div>

            <!-- Auctions Tab -->
            <div class="tab-pane fade {{ $securities->isEmpty() && $auctions->isNotEmpty() ? 'show active' : '' }}" id="auctions" role="tabpanel">
                 @if($auctions->isNotEmpty())
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Auction No.</th>
                                            <th>Security</th>
                                            <th>Auction Date</th>
                                            <th>Amount Sold</th>
                                            <th>Stop Rate</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($auctions as $auc)
                                        <tr>
                                            <td class="fw-bold">{{ $auc->auction_number }}</td>
                                            <td>
                                                <div>{{ $auc->security->security_name }}</div>
                                                <small class="text-muted">{{ $auc->security->isin }}</small>
                                            </td>
                                            <td>{{ $auc->auction_date->format('d M Y') }}</td>
                                            <td>₦{{ number_format($auc->total_amount_sold, 2) }}</td>
                                            <td>{{ $auc->stop_rate }}%</td>
                                            <td>{{ $auc->status }}</td>
                                            <td>
                                                <a href="{{ route('auction-results.show', $auc) }}" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                 @else
                    <div class="text-muted p-3">No auction results found matching your criteria.</div>
                 @endif
            </div>
        </div>
    @endif
</div>
@endsection
