@extends('layouts.app')

@section('title', 'Authoriser Dashboard')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('authoriser.dashboard') }}">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-clipboard-check me-1"></i>Pending Approvals
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-journal-text me-1"></i>Audit Logs
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">Authoriser Dashboard</h2>
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
                            <p class="text-muted mb-1 small">Pending Approvals</p>
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
                            <p class="text-muted mb-1 small">Approved Today</p>
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
                            <p class="text-muted mb-1 small">Rejected Today</p>
                            <h3 class="mb-0 fw-bold text-danger">0</h3>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-3 rounded">
                            <i class="bi bi-x-circle fs-3 text-danger"></i>
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
                            <p class="text-muted mb-1 small">Total Reviewed</p>
                            <h3 class="mb-0 fw-bold">0</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-clipboard-data fs-3 text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pending Approvals Alert -->
    <div class="alert alert-warning border-0 shadow-sm mb-4" role="alert">
        <div class="d-flex align-items-center">
            <i class="bi bi-info-circle fs-4 me-3"></i>
            <div>
                <h6 class="alert-heading mb-1">No pending approvals</h6>
                <p class="mb-0 small">You have no pending actions to review at this time.</p>
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
                            <i class="bi bi-clipboard-check fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">View Pending Approvals</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-journal-text fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">View Audit Logs</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-4">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center py-4">
                            <i class="bi bi-bar-chart fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">View Reports</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Recent Approval Activity</h5>
        </div>
        <div class="card-body">
            <div class="text-center py-5">
                <i class="bi bi-inbox fs-1 text-muted mb-3"></i>
                <h5 class="text-muted">No recent activity</h5>
                <p class="text-muted mb-0">Approval activities will appear here once you start reviewing submissions.</p>
            </div>
        </div>
    </div>
@endsection
