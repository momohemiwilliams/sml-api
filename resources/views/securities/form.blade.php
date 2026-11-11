@extends('layouts.app')

@section('title', $security->exists ? 'Edit Security' : 'Create Security')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link" href="{{ route('securities.index') }}">
            <i class="bi bi-file-earmark-text me-1"></i>Securities
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="#">
            <i class="bi bi-plus-circle me-1"></i>{{ $security->exists ? 'Edit' : 'Create' }} Security
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">{{ $security->exists ? 'Edit' : 'Create New' }} Security</h2>
        <a href="{{ route('securities.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to List
        </a>
    </div>
@endsection

@section('content')
    <form action="{{ $security->exists ? route('inputter.securities.update', $security) : route('inputter.securities.store') }}" 
          method="POST" id="security-form">
        @csrf
        @if($security->exists)
            @method('PUT')
        @endif

        <!-- Basic Information -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Basic Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Product Type <span class="text-danger">*</span></label>
                        <select name="product_type_id" id="product_type_id" class="form-select @error('product_type_id') is-invalid @enderror" required>
                            <option value="">Select Product Type</option>
                            @foreach(\App\Models\ProductType::with('marketCategory')->active()->get() as $type)
                                <option value="{{ $type->id }}" 
                                        {{ (old('product_type_id', $security->product_type_id ?? '') == $type->id) ? 'selected' : '' }}>
                                    {{ $type->marketCategory->name }} - {{ $type->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_type_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">ISIN <span class="text-danger">*</span></label>
                        <input type="text" name="isin" class="form-control @error('isin') is-invalid @enderror" 
                               value="{{ old('isin', $security->isin ?? '') }}" 
                               placeholder="e.g., NGFGN0000001" maxlength="12" required>
                        <small class="text-muted">12-character unique identifier</small>
                        @error('isin')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Security Name <span class="text-danger">*</span></label>
                        <input type="text" name="security_name" class="form-control @error('security_name') is-invalid @enderror" 
                               value="{{ old('security_name', $security->security_name ?? '') }}" required>
                        @error('security_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Issuer <span class="text-danger">*</span></label>
                        <input type="text" name="issuer" class="form-control @error('issuer') is-invalid @enderror" 
                               value="{{ old('issuer', $security->issuer ?? '') }}" required>
                        @error('issuer')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Issuer Category</label>
                        <input type="text" name="issuer_category" class="form-control" 
                               value="{{ old('issuer_category', $security->issuer_category ?? '') }}">
                    </div>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-calendar me-2"></i>Dates</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Issue Date <span class="text-danger">*</span></label>
                        <input type="date" name="issue_date" id="issue_date" class="form-control @error('issue_date') is-invalid @enderror" 
                               value="{{ old('issue_date', $security->issue_date?->format('Y-m-d') ?? '') }}" required>
                        @error('issue_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Maturity Date <span class="text-danger">*</span></label>
                        <input type="date" name="maturity_date" id="maturity_date" class="form-control @error('maturity_date') is-invalid @enderror" 
                               value="{{ old('maturity_date', $security->maturity_date?->format('Y-m-d') ?? '') }}" required>
                        @error('maturity_date')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">First Settlement Date</label>
                        <input type="date" name="first_settlement_date" class="form-control" 
                               value="{{ old('first_settlement_date', $security->first_settlement_date?->format('Y-m-d') ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Last Trading Date</label>
                        <input type="date" name="last_trading_date" class="form-control" 
                               value="{{ old('last_trading_date', $security->last_trading_date?->format('Y-m-d') ?? '') }}">
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
                        <label class="form-label">Face Value <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="face_value" class="form-control @error('face_value') is-invalid @enderror" 
                                   value="{{ old('face_value', $security->face_value ?? '') }}" step="0.01" required>
                        </div>
                        @error('face_value')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Issue Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="issue_price" class="form-control" 
                                   value="{{ old('issue_price', $security->issue_price ?? '') }}" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Coupon Rate (%) <small class="text-muted">For Bonds</small></label>
                        <input type="number" name="coupon_rate" class="form-control" 
                               value="{{ old('coupon_rate', $security->coupon_rate ?? '') }}" step="0.0001" min="0" max="100">
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Coupon Type</label>
                        <select name="coupon_type" class="form-select">
                            <option value="">Select Type</option>
                            <option value="Fixed" {{ old('coupon_type', $security->coupon_type ?? '') == 'Fixed' ? 'selected' : '' }}>Fixed</option>
                            <option value="Floating" {{ old('coupon_type', $security->coupon_type ?? '') == 'Floating' ? 'selected' : '' }}>Floating</option>
                            <option value="Zero" {{ old('coupon_type', $security->coupon_type ?? '') == 'Zero' ? 'selected' : '' }}>Zero Coupon</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Coupon Frequency</label>
                        <select name="coupon_frequency" class="form-select">
                            <option value="">Select Frequency</option>
                            <option value="Annual" {{ old('coupon_frequency', $security->coupon_frequency ?? '') == 'Annual' ? 'selected' : '' }}>Annual</option>
                            <option value="Semi-Annual" {{ old('coupon_frequency', $security->coupon_frequency ?? '') == 'Semi-Annual' ? 'selected' : '' }}>Semi-Annual</option>
                            <option value="Quarterly" {{ old('coupon_frequency', $security->coupon_frequency ?? '') == 'Quarterly' ? 'selected' : '' }}>Quarterly</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Discount Rate (%) <small class="text-muted">For Bills</small></label>
                        <input type="number" name="discount_rate" class="form-control" 
                               value="{{ old('discount_rate', $security->discount_rate ?? '') }}" step="0.0001" min="0" max="100">
                    </div>
                </div>
            </div>
        </div>

        <!-- Calculated Fields (Auto-filled) -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-calculator me-2"></i>Calculated Fields</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Tenor (Years)</label>
                        <input type="number" name="tenor" id="tenor" class="form-control bg-light" 
                               value="{{ old('tenor', $security->tenor ?? '') }}" readonly>
                        <small class="text-muted">Auto-calculated</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">TTM</label>
                        <input type="number" name="ttm" class="form-control bg-light" 
                               value="{{ old('ttm', $security->ttm ?? '') }}" step="0.0001" readonly>
                        <small class="text-muted">Time to Maturity</small>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Effective Coupon</label>
                        <input type="number" name="effective_coupon" class="form-control bg-light" 
                               value="{{ old('effective_coupon', $security->effective_coupon ?? '') }}" step="0.0001" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Day Count Basis</label>
                        <input type="text" name="day_count_basis" class="form-control bg-light" 
                               value="{{ old('day_count_basis', $security->day_count_basis ?? 'Actual/365') }}" readonly>
                    </div>
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
                        <label class="form-label">Outstanding Value</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="outstanding_value" class="form-control" 
                                   value="{{ old('outstanding_value', $security->outstanding_value ?? 0) }}" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount Issued</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="amount_issued" class="form-control" 
                                   value="{{ old('amount_issued', $security->amount_issued ?? '') }}" step="0.01">
                        </div>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label">Amount Outstanding</label>
                        <div class="input-group">
                            <span class="input-group-text">₦</span>
                            <input type="number" name="amount_outstanding" class="form-control" 
                                   value="{{ old('amount_outstanding', $security->amount_outstanding ?? '') }}" step="0.01">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating & Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-star me-2"></i>Rating & Status</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Rating Agency</label>
                        <input type="text" name="rating_agency" class="form-control" 
                               value="{{ old('rating_agency', $security->rating_agency ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Local Rating</label>
                        <input type="text" name="local_rating" class="form-control" 
                               value="{{ old('local_rating', $security->local_rating ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Global Rating</label>
                        <input type="text" name="global_rating" class="form-control" 
                               value="{{ old('global_rating', $security->global_rating ?? '') }}">
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Final Rating</label>
                        <input type="text" name="final_rating" class="form-control bg-light" 
                               value="{{ old('final_rating', $security->final_rating ?? '') }}" readonly>
                        <small class="text-muted">Auto-concatenated</small>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Listing Status <span class="text-danger">*</span></label>
                        <select name="listing_status" class="form-select" required>
                            <option value="Listed" {{ old('listing_status', $security->listing_status ?? 'Listed') == 'Listed' ? 'selected' : '' }}>Listed</option>
                            <option value="Unlisted" {{ old('listing_status', $security->listing_status ?? '') == 'Unlisted' ? 'selected' : '' }}>Unlisted</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="Active" {{ old('status', $security->status ?? 'Active') == 'Active' ? 'selected' : '' }}>Active</option>
                            <option value="Matured" {{ old('status', $security->status ?? '') == 'Matured' ? 'selected' : '' }}>Matured</option>
                            <option value="Redeemed" {{ old('status', $security->status ?? '') == 'Redeemed' ? 'selected' : '' }}>Redeemed</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Remarks</label>
                        <textarea name="remarks" class="form-control" rows="3">{{ old('remarks', $security->remarks ?? '') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <a href="{{ route('securities.index') }}" class="btn btn-secondary">
                        <i class="bi bi-x-circle me-1"></i>Cancel
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>{{ $security->exists ? 'Update' : 'Create' }} Security
                    </button>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('scripts')
<script>
// Auto-calculate tenor when dates change
document.getElementById('issue_date').addEventListener('change', calculateTenor);
document.getElementById('maturity_date').addEventListener('change', calculateTenor);

function calculateTenor() {
    const issueDate = document.getElementById('issue_date').value;
    const maturityDate = document.getElementById('maturity_date').value;
    
    if (issueDate && maturityDate) {
        const issue = new Date(issueDate);
        const maturity = new Date(maturityDate);
        const years = (maturity - issue) / (365.25 * 24 * 60 * 60 * 1000);
        document.getElementById('tenor').value = Math.round(years);
    }
}
</script>
@endpush
