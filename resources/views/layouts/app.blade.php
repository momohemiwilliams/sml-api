<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'SMLARS') }} - @yield('title', 'Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        body {
            overflow-x: hidden;
        }
        
        #sidebar {
            min-height: 100vh;
            transition: all 0.3s;
            background: linear-gradient(180deg, #1e3a8a 0%, #1e40af 100%);
        }
        
        #sidebar.active {
            margin-left: -280px;
        }
        
        #sidebar .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
        }
        
        #sidebar ul.components {
            padding: 20px 0;
        }
        
        #sidebar ul li a {
            padding: 15px 20px;
            font-size: 14px;
            display: block;
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s;
        }
        
        #sidebar ul li a:hover,
        #sidebar ul li a.active {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border-left: 4px solid #fff;
        }
        
        #sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        #content {
            width: 100%;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        
        @media (max-width: 768px) {
            #sidebar {
                margin-left: -280px;
            }
            #sidebar.active {
                margin-left: 0;
            }
        }
    </style>
    
    @stack('styles')
</head>
<body class="bg-light">
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar" class="text-white">
            <!-- Sidebar Header -->
            <div class="sidebar-header">
                <div class="d-flex align-items-center">
                    <i class="bi bi-shield-check fs-3 me-2"></i>
                    <div>
                        <h4 class="mb-0">SMLARS</h4>
                        <small class="opacity-75">FMDQ Exchange</small>
                    </div>
                </div>
            </div>

            <!-- User Info -->
            <div class="px-3 py-3 border-bottom border-white border-opacity-25">
                <div class="d-flex align-items-center">
                    <div class="bg-white bg-opacity-25 rounded-circle p-2 me-2">
                        <i class="bi bi-person-circle fs-5"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-semibold small">{{ Auth::user()->full_name }}</div>
                        <div class="small opacity-75">{{ Auth::user()->getRoleNames()->first() }}</div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <ul class="list-unstyled components">
                <!-- Dashboard -->
                <li>
                    <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        Dashboard
                    </a>
                </li>

                @if(Auth::user()->isSuperAdmin())
                <!-- Super Admin Menu -->
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Administration
                    </div>
                </li>
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-grid"></i>
                        Admin Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        User Management
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.settings') }}" class="{{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                        <i class="bi bi-gear"></i>
                        System Settings
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.audit-logs') }}" class="{{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>
                        Audit Logs
                    </a>
                </li>
                @endif

                <!-- Securities Menu -->
                @can('view-securities')
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Securities
                    </div>
                </li>
                <li>
                    <a href="{{ route('securities.index') }}" class="{{ request()->routeIs('securities.*') ? 'active' : '' }}">
                        <i class="bi bi-file-earmark-text"></i>
                        Securities List
                    </a>
                </li>
                @endcan
                
                @can('view-securities')
                <li>
                    <a href="{{ route('auction-results.index') }}" class="{{ request()->routeIs('auction-results.*') ? 'active' : '' }}">
                        <i class="bi bi-list-columns-reverse"></i>
                        Auction Results
                    </a>
                </li>
                <li>
                    <a href="{{ route('search.advanced') }}" class="{{ request()->routeIs('search.advanced') ? 'active' : '' }}">
                        <i class="bi bi-sliders"></i>
                        Advanced Search
                    </a>
                </li>
                @endcan

                @can('create-securities')
                <li>
                    <a href="{{ route('inputter.securities.create') }}" class="{{ request()->routeIs('inputter.securities.create') ? 'active' : '' }}">
                        <i class="bi bi-plus-circle"></i>
                        Add Security
                    </a>
                </li>
                @endcan

                @can('create-securities')
                <li>
                    <a href="{{ route('inputter.auction-results.create') }}" class="{{ request()->routeIs('inputter.auction-results.create') ? 'active' : '' }}">
                        <i class="bi bi-clock-history"></i>
                        Record Auction
                    </a>
                </li>
                @endcan

                <!-- Inputter Menu -->
                @if(Auth::user()->canBeInputter())
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Inputter
                    </div>
                </li>
                <li>
                    <a href="{{ route('inputter.dashboard') }}" class="{{ request()->routeIs('inputter.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        Inputter Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('inputter.submissions') }}" class="{{ request()->routeIs('inputter.submissions') ? 'active' : '' }}">
                        <i class="bi bi-folder"></i>
                        My Submissions
                    </a>
                </li>
                @endif

                <!-- Authoriser Menu -->
                @if(Auth::user()->canBeAuthoriser())
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Authoriser
                    </div>
                </li>
                <li>
                    <a href="{{ route('authoriser.dashboard') }}" class="{{ request()->routeIs('authoriser.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer2"></i>
                        Authoriser Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('authoriser.pending-approvals') }}" class="{{ request()->routeIs('authoriser.pending-approvals') ? 'active' : '' }}">
                        <i class="bi bi-clipboard-check"></i>
                        Pending Approvals
                        <span class="badge bg-warning text-dark ms-2">0</span>
                    </a>
                </li>
                @endif

                <!-- Reports Menu -->
                @can('view-reports')
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Reports
                    </div>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-bar-chart"></i>
                        Analytics
                    </a>
                </li>
                <li>
                    <a href="#">
                        <i class="bi bi-file-earmark-bar-graph"></i>
                        Reports
                    </a>
                </li>
                @endcan

                <!-- System Admin Menu -->
                @role('super_admin')
                <li class="mt-3">
                    <div class="px-3 py-2 small text-white-50 text-uppercase fw-bold">
                        Admin
                    </div>
                </li>
                <li>
                    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                        <i class="bi bi-speedometer"></i>
                        Admin Dashboard
                    </a>
                </li>
                <li>
                    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                        <i class="bi bi-people"></i>
                        Manage Users
                    </a>
                </li>
                 <li>
                    <a href="{{ route('admin.audit-logs') }}" class="{{ request()->routeIs('admin.audit-logs') ? 'active' : '' }}">
                        <i class="bi bi-journal-text"></i>
                        Audit Logs
                    </a>
                </li>
                @endrole

                <!-- Logout -->
                <li class="mt-4">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-link text-white text-decoration-none w-100 text-start px-3 py-2">
                            <i class="bi bi-box-arrow-right me-2"></i>
                            Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

        <!-- Page Content -->
        <div id="content" class="flex-grow-1">
            <!-- Top Navbar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button type="button" id="sidebarCollapse" class="btn btn-primary">
                        <i class="bi bi-list"></i>
                    </button>

                    <!-- Global Search -->
                    <form class="d-none d-md-flex ms-4" action="{{ route('search.index') }}" method="GET" style="max-width: 400px; width: 100%;">
                        <div class="input-group">
                            <input type="search" name="q" class="form-control bg-light border-0 small" placeholder="Search for ISIN, Security Name, or Auction No..." aria-label="Search" value="{{ request('q') }}">
                            <button class="btn btn-primary" type="button" onclick="this.form.submit()">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>

                    <div class="ms-auto d-flex align-items-center">
                        <!-- Notifications -->
                        <div class="dropdown me-3">
                            <button class="btn btn-link text-dark position-relative" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-bell fs-5"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    0
                                </span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-muted small" href="#">No new notifications</a></li>
                            </ul>
                        </div>

                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <button class="btn btn-link text-dark text-decoration-none dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bi bi-person-circle fs-5 me-1"></i>
                                <span class="d-none d-md-inline">{{ Auth::user()->full_name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <h6 class="dropdown-header">
                                        {{ Auth::user()->full_name }}<br>
                                        <small class="text-muted">{{ Auth::user()->email }}</small>
                                    </h6>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item text-danger">
                                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Header -->
            @hasSection('header')
                <div class="bg-white border-bottom">
                    <div class="container-fluid py-3">
                        @yield('header')
                    </div>
                </div>
            @endif

            <!-- Main Content -->
            <main class="py-4">
                <div class="container-fluid">
                    <!-- Flash Messages -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0 mt-2">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white border-top mt-auto py-3">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="text-muted small mb-0">
                                © {{ date('Y') }} FMDQ Exchange Limited. All rights reserved.
                            </p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-muted small mb-0">
                                SMLARS v1.0 | Last login: {{ Auth::user()->last_login_at?->diffForHumans() ?? 'First time' }}
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    @stack('scripts')
    
    <script>
        // Sidebar toggle
        document.getElementById('sidebarCollapse').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('active');
        });
    </script>
</body>
</html>
