@extends('layouts.app')

@section('title', 'Inputter Dashboard')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('inputter.dashboard') }}">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-file-earmark-text me-1"></i>Securities
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-clipboard-data me-1"></i>Auction Results
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-folder me-1"></i>My Submissions
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">Inputter Dashboard</h2>
        <small class="text-muted">
            Last login: {{ Auth::user()->last_login_at ? Auth::user()->last_login_at->diffForHumans() : 'First time' }}
        </small>
    </div>
@endsection

@section('content')
    <!-- Stats Overview -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">My Submissions</p>
                            <h3 class="mb-0 fw-bold">0</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Pending Approval</p>
                            <h3 class="mb-0 fw-bold text-warning">0</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-3 rounded">
                            <i class="bi bi-clock-history fs-3 text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Approved</p>
                            <h3 class="mb-0 fw-bold text-success">0</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-check-circle fs-3 text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small">Rejected</p>
                            <h3 class="mb-0 fw-bold text-danger">0</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-x-circle fs-3 text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mb-4">
        <h5 class="mb-3 fw-bold">Quick Actions</h5>
        <div class="row g-3">
            <div class="col-md-4">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-plus-circle fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">Add Security</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-clipboard-plus fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">Add Auction Result</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-cloud-upload fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">Bulk Upload</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Submissions -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Recent Submissions</h5>
        </div>
        <div class="card-body">
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">No submissions yet</h5>
                <p class="text-muted mb-4">Get started by creating a new security or auction result.</p>
                <button type="button" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>New Submission
                </button>
            </div>
        </div>
    </div>
@endsection
