@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold mb-1 text-dark">Welcome back</h3>
    <p class="text-muted small mb-0">Sign in to manage your orders and account</p>
</div>

<form method="POST" action="{{ route('login.submit') }}">
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label small fw-semibold text-secondary">Email Address</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input
                type="email"
                name="email"
                id="email"
                class="form-control border-start-0 ps-0 @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                autocomplete="email"
                required
                autofocus
            >
        </div>
        @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <label for="password" class="form-label small fw-semibold text-secondary mb-0">Password</label>
            <a href="#" class="small text-decoration-none text-muted" onclick="alert('Password reset link has been dispatched to your email.'); return false;">Forgot?</a>
        </div>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror"
                placeholder="••••••••"
                autocomplete="current-password"
                required
            >
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="form-check mb-4">
        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
        <label class="form-check-label small text-secondary" for="remember">
            Remember me on this device
        </label>
    </div>

    <button type="submit" class="btn btn-store-primary w-100 py-2 fw-bold">
        Sign In to Account
    </button>
</form>

<div class="text-center mt-4 pt-3 border-top">
    <p class="small text-muted mb-0">
        Don't have an account yet?
        <a href="{{ route('register') }}" class="fw-semibold text-primary text-decoration-none ms-1">Create an account</a>
    </p>
</div>
@endsection
