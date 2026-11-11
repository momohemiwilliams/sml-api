@extends('layouts.app')

@section('title', $auctionResult->exists ? 'Edit Auction Result' : 'Record Auction Result')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link" href="{{ route('auction-results.index') }}">
            <i class="bi bi-list-columns-reverse me-1"></i>Auction Results
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#">
            <i class="bi bi-plus-circle me-1"></i>{{ $auctionResult->exists ? 'Edit' : 'Record' }} Result
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">{{ $auctionResult->exists ? 'Edit' : 'Record' }} Auction Result</h2>
        <a href="{{ route('auction-results.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ $auctionResult->exists ? route('inputter.auction-results.update', $auctionResult) : route('inputter.auction-results.store') }}" 
          method="POST" id="auction-form">
        @csrf
        @if($auctionResult->exists)
            @method('PUT')
        @endif

        <!-- Auction Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Auction Details</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Security <span class="text-danger">*</span></label>
                        <select name="security_id" class="form-select @error('security_id') is-invalid @enderror" required>
                            <option value="">Select Security</option>
                            @foreach($securities as $sec)
                                <option value="{{ $sec->id }}" {{ old('security_id', $auctionResult->security_id) == $sec->id ? 'selected' : '' }}>
                                    {{ $sec->security_name }} ({{ $sec->isin }})
                                </option>
                            @endforeach
                        </select>
                        @error('security_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Auction Number <span class="text-danger">*</span></label>
                        <input type="text" name="auction_number" class="form-control @error('auction_number') is-invalid @enderror" 
                               value="{{ old('auction_number', $auctionResult->auction_number) }}" required>
                        @error('auction_number')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Auction Date <span class="text-danger">*</span></label>
                        <input type="date" name="auction_date" class="form-control @error('auction_date') is-invalid @enderror" 
                               value="{{ old('auction_date', optional($auctionResult->auction_date)->format('Y-m-d')) }}" required>
                        @error('auction_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Value Date <span class="text-danger">*</span></label>
                        <input type="date" name="value_date" class="form-control @error('value_date') is-invalid @enderror" 
                               value="{{ old('value_date', optional($auctionResult->value_date)->format('Y-m-d')) }}" required>
                        @error('value_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Tenor (Days) <span class="text-danger">*</span></label>
                        <input type="number" name="tenor_days" class="form-control" 
                               value="{{ old('tenor_days', $auctionResult->tenor_days) }}" required min="1">
                    </div>
                    
                     <div class="col-md-3">
                        <label class="form-label">Auction Type</label>
                        <select name="auction_type" class="form-select">
                            <option value="Primary" {{ old('auction_type', $auctionResult->auction_type ?? 'Primary') == 'Primary' ? 'selected' : '' }}>Primary</option>
                            <option value="Secondary" {{ old('auction_type', $auctionResult->auction_type) == 'Secondary' ? 'selected' : '' }}>Secondary</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Amounts -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-cash-stack me-2"></i>Amounts</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Amount Offered <span class="text-danger">*</span></label>
                        <div class="input-group">
                             <span class="input-group-text">₦</span>
                             <input type="number" name="amount_offered" class="form-control" step="0.01"
                                    value="{{ old('amount_offered', $auctionResult->amount_offered) }}" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Amount Subscribed <span class="text-danger">*</span></label>
                        <div class="input-group">
                             <span class="input-group-text">₦</span>
                             <input type="number" name="amount_subscribed" class="form-control" step="0.01"
                                    value="{{ old('amount_subscribed', $auctionResult->amount_subscribed) }}" required>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Amount Sold <span class="text-danger">*</span></label>
                        <div class="input-group">
                             <span class="input-group-text">₦</span>
                             <input type="number" name="amount_sold" class="form-control" step="0.01"
                                    value="{{ old('amount_sold', $auctionResult->amount_sold) }}" required>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Non-Competitive Sales</label>
                        <div class="input-group">
                             <span class="input-group-text">₦</span>
                             <input type="number" name="non_competitive_sales" class="form-control" step="0.01"
                                    value="{{ old('non_competitive_sales', $auctionResult->non_competitive_sales ?? 0) }}">
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Total Amount Sold</label>
                         <div class="input-group">
                             <span class="input-group-text">₦</span>
                             <input type="number" class="form-control bg-light" value="{{ old('total_amount_sold', $auctionResult->total_amount_sold) }}" readonly placeholder="Auto-calc">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rates -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-percent me-2"></i>Rates & Yields</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Stop Rate (%) <span class="text-danger">*</span></label>
                        <input type="number" name="stop_rate" class="form-control" step="0.0001"
                               value="{{ old('stop_rate', $auctionResult->stop_rate) }}" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Marginal Rate (%)</label>
                        <input type="number" name="marginal_rate" class="form-control" step="0.0001"
                               value="{{ old('marginal_rate', $auctionResult->marginal_rate) }}">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">True Yield (%)</label>
                        <input type="number" name="true_yield" class="form-control" step="0.0001"
                               value="{{ old('true_yield', $auctionResult->true_yield) }}">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status & Remarks -->
        <div class="card border-0 shadow-sm mb-4">
             <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-chat-text me-2"></i>Status & Remarks</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="Completed" {{ old('status', $auctionResult->status ?? 'Completed') == 'Completed' ? 'selected' : '' }}>Completed</option>
                            <option value="Reopened" {{ old('status', $auctionResult->status) == 'Reopened' ? 'selected' : '' }}>Reopened</option>
                            <option value="Cancelled" {{ old('status', $auctionResult->status) == 'Cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="1">{{ old('remarks', $auctionResult->remarks) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('auction-results.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>{{ $auctionResult->exists ? 'Update' : 'Record' }} Result
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection
