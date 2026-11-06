<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Authentication') | {{ config('app.name', 'MONO') }}</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Unified Custom Design System -->
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ filemtime(public_path('css/theme.css')) }}">

    @stack('styles')
</head>
<body class="auth-page">

    <div class="container d-flex flex-column align-items-center justify-content-center">
        {{-- Brand / Back Link --}}
        <div class="mb-4 text-center">
            <a href="{{ route('store.home') }}" class="store-brand d-inline-flex align-items-center gap-2 text-decoration-none">
                <span class="brand-badge"><i class="bi bi-bag-check-fill"></i></span>
                <span class="store-brand-text fs-3">{{ config('app.name', 'MONO') }}</span>
            </a>
        </div>

        {{-- Main Auth Card --}}
        <div class="auth-card">
            @if (session('status'))
                <div class="alert alert-success d-flex align-items-center gap-2 rounded-3 border-0 shadow-xs mb-4">
                    <i class="bi bi-check-circle-fill"></i>
                    <div>{{ session('status') }}</div>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 border-0 shadow-xs mb-4">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger rounded-3 border-0 shadow-xs mb-4">
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </div>

        {{-- Footer link --}}
        <div class="mt-4 text-center small text-muted">
            <a href="{{ route('store.home') }}" class="text-secondary text-decoration-none hover-text-dark">
                <i class="bi bi-arrow-left me-1"></i> Return to store
            </a>
            <div class="mt-2 text-secondary opacity-75">
                &copy; {{ date('Y') }} {{ config('app.name', 'MONO') }}. All rights reserved.
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
