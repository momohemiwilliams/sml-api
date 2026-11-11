@extends('layouts.guest')

@section('title', 'Login')

@section('content')
<div class="container">
    <div class="row justify-content-center min-vh-100 align-items-center">
        <div class="col-md-5">
            <!-- Logo and Branding -->
            <div class="text-center mb-4">
                <div class="mb-3">
                    <svg width="60" height="60" fill="currentColor" class="text-primary" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="fw-bold mb-2">SMLARS</h2>
                <p class="text-muted mb-1">Security Master List and Auction Result System</p>
                <p class="text-muted small">FMDQ Exchange Limited</p>
            </div>

            <!-- Login Card -->
            <div class="card shadow-lg border-0">
                <div class="card-body p-4 p-md-5">
                    <h4 class="card-title mb-4">Sign in to your account</h4>

                    <!-- Success Message -->
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle me-2"></i>
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Error Messages -->
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            @foreach($errors->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    @endif

                    <!-- Login Form -->
                    <form action="{{ route('login') }}" method="POST">
                        @csrf

                        <!-- Email Field -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input 
                                type="email" 
                                class="form-control form-control-lg @error('email') is-invalid @enderror" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}"
                                placeholder="you@fmdqgroup.com"
                                required 
                                autofocus
                            >
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password Field -->
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input 
                                type="password" 
                                class="form-control form-control-lg @error('password') is-invalid @enderror" 
                                id="password" 
                                name="password" 
                                placeholder="••••••••"
                                required
                            >
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Remember Me & Forgot Password -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                <label class="form-check-label" for="remember">
                                    Remember me
                                </label>
                            </div>
                            <a href="#" class="text-decoration-none">Forgot password?</a>
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">
                                Sign in
                            </button>
                        </div>
                    </form>

                    <!-- Default Credentials (Development Only) -->
                    @if(config('app.env') === 'local')
                        <div class="mt-4 pt-4 border-top">
                            <p class="small fw-bold text-muted mb-2">Default Credentials (Development):</p>
                            <div class="small text-muted">
                                <p class="mb-1"><strong>Super Admin:</strong> admin@fmdqgroup.com / password</p>
                                <p class="mb-1"><strong>Inputter:</strong> inputter@fmdqgroup.com / password</p>
                                <p class="mb-0"><strong>Authoriser:</strong> authoriser@fmdqgroup.com / password</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Footer -->
            <p class="text-center text-muted small mt-4">
                © {{ date('Y') }} FMDQ Exchange Limited. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection
