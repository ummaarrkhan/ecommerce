<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin Dashboard') | {{ config('app.name', 'MONO') }} Admin</title>

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
<body>

    <div class="admin-wrapper">
        {{-- Sidebar --}}
        <aside class="admin-sidebar d-none d-lg-flex">
            <div class="admin-sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand text-decoration-none">
                    <span class="badge-admin">Admin</span>
                    <span>{{ config('app.name', 'MONO') }}</span>
                </a>
            </div>

            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>

                <a href="{{ route('admin.products.index') }}" class="admin-nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>

                <a href="{{ route('admin.categories.index') }}" class="admin-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i>
                    <span>Categories</span>
                </a>

                <a href="{{ route('admin.orders.index') }}" class="admin-nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>
                    <span>Orders</span>
                </a>

                <hr class="border-secondary opacity-25 my-3">

                <a href="{{ route('store.home') }}" target="_blank" class="admin-nav-item text-secondary">
                    <i class="bi bi-arrow-up-right-square"></i>
                    <span>View Storefront</span>
                </a>
            </nav>

            <div class="admin-sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 34px; height: 34px; font-size: 13px;">
                            {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <div class="small fw-bold text-white text-truncate" style="max-width: 120px;">
                                {{ auth()->user()->name ?? 'Admin' }}
                            </div>
                            <div class="text-secondary" style="font-size: 11px;">Administrator</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger border-0 p-1" title="Sign Out">
                            <i class="bi bi-box-arrow-right fs-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Mobile Offcanvas Sidebar --}}
        <div class="offcanvas offcanvas-start bg-dark text-white admin-sidebar" tabindex="-1" id="adminSidebarMobile" aria-labelledby="adminSidebarMobileLabel">
            <div class="admin-sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="admin-brand text-decoration-none">
                    <span class="badge-admin">Admin</span>
                    <span>{{ config('app.name', 'MONO') }}</span>
                </a>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <nav class="admin-nav">
                <a href="{{ route('admin.dashboard') }}" class="admin-nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="admin-nav-item {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <i class="bi bi-box-seam"></i>
                    <span>Products</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="admin-nav-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="bi bi-tags"></i>
                    <span>Categories</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="admin-nav-item {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <i class="bi bi-receipt"></i>
                    <span>Orders</span>
                </a>
                <hr class="border-secondary opacity-25 my-3">
                <a href="{{ route('store.home') }}" target="_blank" class="admin-nav-item text-secondary">
                    <i class="bi bi-arrow-up-right-square"></i>
                    <span>View Storefront</span>
                </a>
            </nav>
            <div class="admin-sidebar-footer">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="small text-white">
                        {{ auth()->user()->name ?? 'Admin' }}
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                            <i class="bi bi-box-arrow-right"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Main Page Body --}}
        <div class="admin-main-content">
            {{-- Topbar --}}
            <header class="admin-topbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-sm btn-light border d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebarMobile">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h5 class="fw-bold mb-0 text-dark">@yield('page_title', 'Management Console')</h5>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('store.home') }}" target="_blank" class="btn btn-store-outline btn-store-sm d-none d-sm-inline-flex align-items-center gap-1">
                        <i class="bi bi-shop"></i> View Store
                    </a>

                    <div class="dropdown">
                        <button class="btn btn-light btn-sm border d-flex align-items-center gap-2 rounded-pill px-3 py-1" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shield-check text-primary"></i>
                            <span class="small fw-semibold">{{ auth()->user()->name ?? 'Admin' }}</span>
                            <i class="bi bi-chevron-down small text-muted"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 p-2 mt-2">
                            <li><a class="dropdown-item rounded-2 py-2" href="{{ route('store.account') }}"><i class="bi bi-person me-2"></i>My Account</a></li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item rounded-2 py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i>Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            {{-- Flash Messages --}}
            <div class="admin-page-body">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-xs rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div>{{ session('error') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-xs rounded-3 mb-4" role="alert">
                        <div class="fw-bold mb-1"><i class="bi bi-exclamation-circle-fill me-1"></i> Form Submission Errors:</div>
                        <ul class="mb-0 ps-3 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
