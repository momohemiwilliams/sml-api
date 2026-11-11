@extends('layouts.app')

@section('title', 'Admin Dashboard')

@section('nav-links')
    <li class="nav-item">
        <a class="nav-link active" href="{{ route('admin.dashboard') }}">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-people me-1"></i>Users
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="#">
            <i class="bi bi-gear me-1"></i>Settings
        </a>
    </li>
@endsection

@section('header')
    <div class="d-flex justify-content-between align-items-center">
        <h2 class="mb-0 fw-bold">Super Admin Dashboard</h2>
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
                            <p class="text-muted mb-1 small">Total Users</p>
                            <h3 class="mb-0 fw-bold">{{ \App\Models\User::count() }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-people fs-3 text-primary"></i>
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
                            <p class="text-muted mb-1 small">Active Users</p>
                            <h3 class="mb-0 fw-bold text-success">{{ \App\Models\User::active()->count() }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 p-3 rounded">
                            <i class="bi bi-person-check fs-3 text-success"></i>
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
                            <p class="text-muted mb-1 small">Total Securities</p>
                            <h3 class="mb-0 fw-bold text-primary">0</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded">
                            <i class="bi bi-file-earmark-text fs-3 text-primary"></i>
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
            <div class="col-md-3">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <i class="bi bi-person-plus fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">Create User</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <i class="bi bi-file-earmark-bar-graph fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">View Reports</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <i class="bi bi-gear fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">System Settings</p>
                        </div>
                    </div>
                </a>
            </div>

            <div class="col-md-3">
                <a href="#" class="text-decoration-none">
                    <div class="card border-2 border-dashed h-100 text-center">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <i class="bi bi-clipboard-data fs-1 text-primary mb-2"></i>
                            <p class="mb-0 fw-semibold">Audit Logs</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0 fw-bold">Recent Activity</h5>
        </div>
        <div class="card-body">
            <div class="list-group list-group-flush">
                <div class="list-group-item border-0 px-0">
                    <div class="d-flex align-items-start">
                        <div class="bg-success rounded-circle p-2 me-3">
                            <i class="bi bi-check-circle text-white"></i>
                        </div>
                        <div class="flex-grow-1">
                            <p class="mb-1">System initialized with default users and roles</p>
                            <small class="text-muted">Just now</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
