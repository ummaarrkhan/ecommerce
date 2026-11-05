@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
<div class="text-center mb-4">
    <h3 class="fw-bold mb-1 text-dark">Create Account</h3>
    <p class="text-muted small mb-0">Join {{ config('app.name', 'SHOPX') }} for seamless checkout</p>
</div>

<form method="POST" action="{{ route('register.submit') }}">
    @csrf

    <div class="mb-3">
        <label for="name" class="form-label small fw-semibold text-secondary">Full Name</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input
                type="text"
                name="name"
                id="name"
                class="form-control border-start-0 ps-0 @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                placeholder="Jane Doe"
                autocomplete="name"
                required
                autofocus
            >
        </div>
        @error('name')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

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
                placeholder="jane@example.com"
                autocomplete="email"
                required
            >
        </div>
        @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-3">
        <label for="password" class="form-label small fw-semibold text-secondary">Password</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input
                type="password"
                name="password"
                id="password"
                class="form-control border-start-0 ps-0 @error('password') is-invalid @enderror"
                placeholder="At least 8 characters"
                autocomplete="new-password"
                required
            >
        </div>
        @error('password')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label small fw-semibold text-secondary">Confirm Password</label>
        <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-shield-check"></i></span>
            <input
                type="password"
                name="password_confirmation"
                id="password_confirmation"
                class="form-control border-start-0 ps-0"
                placeholder="Repeat password"
                autocomplete="new-password"
                required
            >
        </div>
    </div>

    <button type="submit" class="btn btn-store-primary w-100 py-2 fw-bold">
        Create Account
    </button>
</form>

<div class="text-center mt-4 pt-3 border-top">
    <p class="small text-muted mb-0">
        Already have an account?
        <a href="{{ route('login') }}" class="fw-semibold text-primary text-decoration-none ms-1">Sign in here</a>
    </p>
</div>
@endsection
