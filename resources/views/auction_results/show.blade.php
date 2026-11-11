@extends('layouts.app')

@section('title', 'Auction Result Details')

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-0 fw-bold">Auction #{{ $auctionResult->auction_number }}</h2>
            <p class="text-muted mb-0">
                {{ $auctionResult->security->security_name }} ({{ $auctionResult->security->isin }})
            </p>
        </div>
        <div>
            <a href="{{ route('auction-results.index') }}" class="btn btn-secondary me-2">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
            @can('role:inputter|super_admin')
            <a href="{{ route('inputter.auction-results.edit', $auctionResult) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
        </div>
    </div>
@endsection

@section('content')
<div class="row g-4">
    <!-- Key Info -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
            <div class="card-header bg-white fw-bold">
                <i class="bi bi-info-circle me-2 text-primary"></i>Auction Information
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th class="text-muted w-50">Auction Date</th>
                        <td>{{ $auctionResult->auction_date->format('d M, Y') }} ({{ $auctionResult->day_of_week }})</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Value Date</th>
                        <td>{{ $auctionResult->value_date->format('d M, Y') }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Tenor</th>
                        <td>{{ $auctionResult->tenor_days }} Days</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Type</th>
                        <td>{{ $auctionResult->auction_type }}</td>
                    </tr>
                     <tr>
                        <th class="text-muted">Status</th>
                        <td>
                             <span class="badge bg-{{ $auctionResult->status === 'Completed' ? 'success' : 'warning' }}">
                                {{ $auctionResult->status }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Amounts -->
    <div class="col-md-6">
        <div class="card shadow-sm h-100 border-0">
             <div class="card-header bg-white fw-bold">
                <i class="bi bi-cash-stack me-2 text-success"></i>Results Overview
            </div>
             <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th class="text-muted w-50">Amount Offered</th>
                        <td class="fw-bold">₦{{ number_format($auctionResult->amount_offered, 2) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Amount Subscribed</th>
                        <td>₦{{ number_format($auctionResult->amount_subscribed, 2) }}</td>
                    </tr>
                    <tr>
                        <th class="text-muted">Amount Solt (Total)</th>
                        <td class="fw-bold text-success">₦{{ number_format($auctionResult->total_amount_sold, 2) }}</td>
                    </tr>
                     <tr>
                        <th class="text-muted ps-4"><small>Competitive</small></th>
                        <td><small>₦{{ number_format($auctionResult->amount_sold, 2) }}</small></td>
                    </tr>
                     <tr>
                        <th class="text-muted ps-4"><small>Non-Competitive</small></th>
                        <td><small>₦{{ number_format($auctionResult->non_competitive_sales, 2) }}</small></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Rates & Yields -->
    <div class="col-md-6">
         <div class="card shadow-sm h-100 border-0">
             <div class="card-header bg-white fw-bold">
                <i class="bi bi-percent me-2 text-info"></i>Rates & Outcomes
            </div>
             <div class="card-body">
                <div class="row text-center">
                    <div class="col-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Stop Rate</div>
                            <div class="h4 mb-0 text-primary">{{ $auctionResult->stop_rate }}%</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="p-3 bg-light rounded">
                            <div class="text-muted small">Marginal Rate</div>
                            <div class="h4 mb-0">{{ $auctionResult->marginal_rate ?? '-' }}%</div>
                        </div>
                    </div>
                    <div class="col-4">
                         <div class="p-3 bg-light rounded">
                            <div class="text-muted small">True Yield</div>
                            <div class="h4 mb-0">{{ $auctionResult->true_yield ?? '-' }}%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Ratios -->
    <div class="col-md-6">
         <div class="card shadow-sm h-100 border-0">
             <div class="card-header bg-white fw-bold">
                <i class="bi bi-pie-chart me-2 text-warning"></i>Performance Ratios
            </div>
             <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th class="text-muted w-50">Bid-to-Cover Ratio</th>
                        <td class="h5 {{ ($auctionResult->bid_cover_ratio > 2) ? 'text-success' : '' }}">
                            {{ $auctionResult->bid_cover_ratio }}x
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Subscription Level</th>
                        <td class="h5">
                             {{ number_format($auctionResult->subscription_level, 2) }}%
                        </td>
                    </tr>
                </table>
                 <div class="mt-2">
                     <label class="small text-muted mb-1">Subscription Progress</label>
                     <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ min($auctionResult->subscription_level, 100) }}%" 
                             aria-valuenow="{{ $auctionResult->subscription_level }}" aria-valuemin="0" aria-valuemax="100"></div>
                     </div>
                 </div>
            </div>
        </div>
    </div>
    
    <!-- Audit Info -->
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-body small text-muted">
                <div class="row">
                    <div class="col-md-3">
                        <i class="bi bi-person me-1"></i> Recorded By: 
                        {{ $auctionResult->creator->name ?? 'Unknown' }}
                    </div>
                    <div class="col-md-3">
                        <i class="bi bi-clock me-1"></i> Created: 
                        {{ $auctionResult->created_at->format('d M Y H:i') }}
                    </div>
                    <div class="col-md-3">
                         <i class="bi bi-clock-history me-1"></i> Last Updated: 
                        {{ $auctionResult->updated_at->format('d M Y H:i') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
