@extends('layouts.app')

@section('title', 'Securities Master List')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('securities.index') }}">
            <i class="bi bi-file-earmark-text me-1"></i>Securities
        </a>
    </li>
    @can('create-securities')
    <li class="nav-item">
        <a class="nav-link" href="{{ route('inputter.securities.create') }}">
            <i class="bi bi-plus-circle me-1"></i>Add Security
        </a>
    </li>
    @endcan
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">Securities Master List</h2>
        <div>
            @can('create-securities')
            <a href="{{ route('inputter.securities.create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Add New Security
            </a>
            @endcan
        </div>
    </div>
@endsection

@section('content')
    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('securities.index') }}" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Product Type</label>
                    <select name="product_type" class="form-select">
                        <option value="">All Product Types</option>
                        @foreach(\App\Models\ProductType::active()->get() as $type)
                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="Active">Active</option>
                        <option value="Matured">Matured</option>
                        <option value="Redeemed">Redeemed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Issuer</label>
                    <input type="text" name="issuer" class="form-control" placeholder="Search issuer...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-search me-1"></i>Filter
                        </button>
                        <a href="{{ route('securities.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Export Buttons -->
    <div class="mb-3">
        <div class="btn-group" role="group">
            <a href="{{ route('securities.export.excel') }}" class="btn btn-success">
                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
            </a>
            <a href="{{ route('securities.export.pdf') }}" class="btn btn-danger">
                <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
            </a>
        </div>
        @can('create-securities')
        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="bi bi-cloud-upload me-1"></i>Import Excel
        </button>
        @endcan
    </div>

    <!-- Securities Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="securities-table">
                    <thead class="table-light">
                        <tr>
                            <th>ISIN</th>
                            <th>Security Name</th>
                            <th>Issuer</th>
                            <th>Product Type</th>
                            <th>Issue Date</th>
                            <th>Maturity Date</th>
                            <th>Face Value</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse(\App\Models\Security::with('productType')->latest()->paginate(15) as $security)
                        <tr>
                            <td><code>{{ $security->isin }}</code></td>
                            <td>{{ $security->security_name }}</td>
                            <td>{{ $security->issuer }}</td>
                            <td>
                                <span class="badge bg-primary">{{ $security->productType->name ?? 'N/A' }}</span>
                            </td>
                            <td>{{ $security->issue_date?->format('d M Y') }}</td>
                            <td>{{ $security->maturity_date?->format('d M Y') }}</td>
                            <td>₦{{ number_format($security->face_value, 2) }}</td>
                            <td>
                                @if($security->status === 'Active')
                                    <span class="badge bg-success">Active</span>
                                @elseif($security->status === 'Matured')
                                    <span class="badge bg-warning">Matured</span>
                                @else
                                    <span class="badge bg-secondary">{{ $security->status }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('securities.show', $security) }}" class="btn btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @can('edit-securities')
                                    <a href="{{ route('inputter.securities.edit', $security) }}" class="btn btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-2">No securities found</p>
                                @can('create-securities')
                                <a href="{{ route('inputter.securities.create') }}" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>Add First Security
                                </a>
                                @endcan
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-3">
                {{ \App\Models\Security::paginate(15)->links() }}
            </div>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Securities from Excel</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('securities.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Select Excel File</label>
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                            <small class="text-muted">Supported formats: .xlsx, .xls</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <a href="#" class="alert-link">Download template</a> to see the required format.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cloud-upload me-1"></i>Import
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
