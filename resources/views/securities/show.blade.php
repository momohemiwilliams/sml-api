@extends('layouts.app')

@section('title', 'Security Details')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link" href="{{ route('securities.index') }}">
            <i class="bi bi-file-earmark-text me-1"></i>Securities
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#">
            <i class="bi bi-eye me-1"></i>View Security
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h2 class="mb-0 fw-bold">{{ $security->security_name }}</h2>
            <p class="text-muted mb-0">ISIN: <code>{{ $security->isin }}</code></p>
        </div>
        <div>
            @can('edit-securities')
            <a href="{{ route('inputter.securities.edit', $security) }}" class="btn btn-warning">
                <i class="bi bi-pencil me-1"></i>Edit
            </a>
            @endcan
            <a href="{{ route('securities.index') }}" class="btn btn-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back
            </a>
        </div>
    </div>
@endsection

@section('content')
    <!-- Status Badge -->
    <div class="mb-4">
        @if($security->status === 'Active')
            <span class="badge bg-success fs-6">Active</span>
        @elseif($security->status === 'Matured')
            <span class="badge bg-warning fs-6">Matured</span>
        @else
            <span class="badge bg-secondary fs-6">{{ $security->status }}</span>
        @endif
    </div>

    <!-- Basic Information -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="text-muted small">Product Type</label>
                    <p class="fw-bold">{{ $security->productType->name ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Issuer</label>
                    <p class="fw-bold">{{ $security->issuer }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Issuer Category</label>
                    <p class="fw-bold">{{ $security->issuer_category ?? 'N/A' }}</p>
                </div>
                <div class="col-md-6">
                    <label class="text-muted small">Listing Status</label>
                    <p class="fw-bold">{{ $security->listing_status }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Dates -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-calendar me-2"></i>Important Dates</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Issue Date</label>
                    <p class="fw-bold">{{ $security->issue_date?->format('d M Y') }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Maturity Date</label>
                    <p class="fw-bold">{{ $security->maturity_date?->format('d M Y') }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">First Settlement Date</label>
                    <p class="fw-bold">{{ $security->first_settlement_date?->format('d M Y') ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Last Trading Date</label>
                    <p class="fw-bold">{{ $security->last_trading_date?->format('d M Y') ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Details -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Financial Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="text-muted small">Face Value</label>
                    <p class="fw-bold fs-5 text-primary">₦{{ number_format($security->face_value, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Issue Price</label>
                    <p class="fw-bold">₦{{ number_format($security->issue_price ?? 0, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Tenor</label>
                    <p class="fw-bold">{{ $security->tenor ?? 'N/A' }} years</p>
                </div>
                @if($security->coupon_rate)
                <div class="col-md-4">
                    <label class="text-muted small">Coupon Rate</label>
                    <p class="fw-bold">{{ number_format($security->coupon_rate, 4) }}%</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Coupon Type</label>
                    <p class="fw-bold">{{ $security->coupon_type ?? 'N/A' }}</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Coupon Frequency</label>
                    <p class="fw-bold">{{ $security->coupon_frequency ?? 'N/A' }}</p>
                </div>
                @endif
                @if($security->discount_rate)
                <div class="col-md-4">
                    <label class="text-muted small">Discount Rate</label>
                    <p class="fw-bold">{{ number_format($security->discount_rate, 4) }}%</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Outstanding Values -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Outstanding Values</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="text-muted small">Outstanding Value</label>
                    <p class="fw-bold">₦{{ number_format($security->outstanding_value ?? 0, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Amount Issued</label>
                    <p class="fw-bold">₦{{ number_format($security->amount_issued ?? 0, 2) }}</p>
                </div>
                <div class="col-md-4">
                    <label class="text-muted small">Amount Outstanding</label>
                    <p class="fw-bold">₦{{ number_format($security->amount_outstanding ?? 0, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rating Information -->
    @if($security->rating_agency || $security->local_rating || $security->global_rating)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-star me-2"></i>Rating Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Rating Agency</label>
                    <p class="fw-bold">{{ $security->rating_agency ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Local Rating</label>
                    <p class="fw-bold">{{ $security->local_rating ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Global Rating</label>
                    <p class="fw-bold">{{ $security->global_rating ?? 'N/A' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Final Rating</label>
                    <p class="fw-bold">{{ $security->final_rating ?? 'N/A' }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Remarks -->
    @if($security->remarks)
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Remarks</h5>
        </div>
        <div class="card-body">
            <p class="mb-0">{{ $security->remarks }}</p>
        </div>
    </div>
    @endif

    <!-- Audit Information -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Audit Information</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="text-muted small">Created By</label>
                    <p class="fw-bold">{{ $security->creator->full_name ?? 'System' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Created At</label>
                    <p class="fw-bold">{{ $security->created_at?->format('d M Y H:i') }}</p>
                </div>
                <div class="col-md-3">
                    <label class="text-muted small">Last Updated</label>
                    <p class="fw-bold">{{ $security->updated_at?->format('d M Y H:i') }}</p>
                </div>
                @if($security->approved_by)
                <div class="col-md-3">
                    <label class="text-muted small">Approved By</label>
                    <p class="fw-bold">{{ $security->approver->full_name ?? 'N/A' }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>
@endsection
