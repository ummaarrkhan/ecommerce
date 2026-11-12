<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name', 'MONO') . ' — Modern Curated Essentials')</title>

    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5.3.3 CSS & Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Unified Custom Design System -->
    <link rel="stylesheet" href="{{ asset('css/theme.css') }}?v={{ @filemtime(public_path('css/theme.css')) ?: '1.0' }}">

    @stack('styles')
</head>
<body class="d-flex flex-column min-vh-100">

    {{-- Top Announcement Bar --}}
    <div class="store-announcement text-center">
        <div class="container d-flex justify-content-center align-items-center gap-2">
            <span>✨ Free express shipping on orders over $75</span>
            <span class="opacity-50">•</span>
            <span>Hassle-free 30-day returns</span>
        </div>
    </div>

    {{-- Main Navbar --}}
    <header class="store-navbar">
        <nav class="navbar navbar-expand-lg py-2">
            <div class="container">
                {{-- Brand Logo --}}
                <a class="store-brand" href="{{ route('store.home') }}">
                    <span class="brand-badge"><i class="bi bi-bag-check-fill"></i></span>
                    <span class="store-brand-text">{{ config('app.name', 'MONO') }}</span>
                </a>

                {{-- Mobile Toggle --}}
                <div class="d-flex align-items-center gap-2 d-lg-none">
                    @php
                        $cartCount = collect(session('cart', []))->sum(function ($item) {
                            return is_array($item) ? ($item['quantity'] ?? 0) : (int) $item;
                        });
                        $wishlistCount = auth()->check()
                            ? auth()->user()->wishlists()->count()
                            : count((array) session('wishlist', []));
                    @endphp

                    <a href="{{ route('store.wishlist.index') }}" class="store-cart-btn" title="Wishlist">
                        <i class="bi bi-heart"></i>
                        @if ($wishlistCount > 0)
                            <span class="store-icon-badge">{{ $wishlistCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('store.cart.index') }}" class="store-cart-btn" title="Cart" data-cart-drawer-toggle>
                        <i class="bi bi-bag"></i>
                        <span class="store-cart-count" data-cart-count style="{{ $cartCount > 0 ? '' : 'display: none;' }}">{{ $cartCount }}</span>
                    </a>

                    <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#storeNavbarContent" aria-controls="storeNavbarContent" aria-expanded="false" aria-label="Toggle navigation">
                        <i class="bi bi-list fs-3"></i>
                    </button>
                </div>

                {{-- Navbar Links & Search --}}
                <div class="collapse navbar-collapse" id="storeNavbarContent">
                    {{-- Nav links --}}
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3 gap-1">
                        <li class="nav-item">
                            <a class="nav-link-custom {{ request()->routeIs('store.home') && !request()->hasAny(['search', 'category_id']) ? 'active' : '' }}" href="{{ route('store.home') }}">
                                Home
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-custom" href="{{ route('store.home') }}#featured-products">
                                Shop All
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link-custom" href="{{ route('store.home') }}#categories">
                                Categories
                            </a>
                        </li>
                    </ul>

                    {{-- Live Search Form --}}
                    <form class="store-search-form my-2 my-lg-0 me-lg-3" method="GET" action="{{ route('store.home') }}" data-live-search>
                        <i class="bi bi-search store-search-icon"></i>
                        <input
                            class="form-control store-search-input"
                            type="search"
                            name="search"
                            placeholder="Search products, brands, tags..."
                            value="{{ request('search') }}"
                            aria-label="Search products"
                            autocomplete="off"
                            data-search-input
                        >
                        <button type="button" class="store-search-clear d-none" data-search-clear aria-label="Clear search">
                            <i class="bi bi-x-circle-fill"></i>
                        </button>
                        <div class="store-search-spinner d-none" data-search-spinner>
                            <span class="spinner-border spinner-border-sm" role="status"></span>
                        </div>

                        {{-- Live Dropdown Autocomplete --}}
                        <div class="store-search-dropdown d-none" data-search-dropdown>
                            <div class="store-search-categories p-2 border-bottom d-none" data-search-categories></div>
                            <div class="store-search-results" data-search-results></div>
                            <div class="store-search-empty p-4 text-center d-none" data-search-empty>
                                <i class="bi bi-search text-muted fs-3 mb-2 d-block"></i>
                                <div class="small text-muted mb-0">No products found matching "<strong data-search-empty-term></strong>"</div>
                            </div>
                            <div class="store-search-footer p-2 text-center border-top d-none" data-search-footer>
                                <a href="#" class="store-search-all-link" data-search-view-all>
                                    See all results <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </form>

                    {{-- User Actions --}}
                    <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                        @auth
                            <div class="dropdown">
                                <button class="btn btn-store-outline btn-store-sm dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-person-circle fs-6"></i>
                                    <span>{{ Str::limit(auth()->user()->name, 14) }}</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 mt-2 p-2">
                                    <li class="px-3 py-2 border-bottom mb-1">
                                        <div class="small fw-bold text-dark">{{ auth()->user()->name }}</div>
                                        <div class="text-muted small text-truncate" style="max-width: 180px;">{{ auth()->user()->email }}</div>
                                    </li>
                                    <li>
                                        <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2" href="{{ route('store.account') }}">
                                            <i class="bi bi-box-seam text-muted"></i> My Account & Orders
                                        </a>
                                    </li>
                                    @hasanyrole('super-admin|admin')
                                        <li>
                                            <a class="dropdown-item rounded-2 py-2 d-flex align-items-center gap-2 text-primary" href="{{ route('admin.dashboard') }}">
                                                <i class="bi bi-speedometer2"></i> Admin Dashboard
                                            </a>
                                        </li>
                                    @endhasanyrole
                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item rounded-2 py-2 text-danger d-flex align-items-center gap-2">
                                                <i class="bi bi-box-arrow-right"></i> Sign Out
                                            </button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn btn-store-outline btn-store-sm">
                                <i class="bi bi-person me-1"></i> Sign In
                            </a>
                            <a href="{{ route('register') }}" class="btn btn-store-primary btn-store-sm d-none d-sm-inline-flex">
                                Register
                            </a>
                        @endauth

                        {{-- Wishlist Button (Desktop) --}}
                        <a href="{{ route('store.wishlist.index') }}" class="store-cart-btn d-none d-lg-inline-flex" title="View Wishlist">
                            <i class="bi bi-heart"></i>
                            @if ($wishlistCount > 0)
                                <span class="store-icon-badge">{{ $wishlistCount }}</span>
                            @endif
                        </a>

                        {{-- Cart Button (Desktop) --}}
                        <a href="{{ route('store.cart.index') }}" class="store-cart-btn d-none d-lg-inline-flex" title="View Cart" data-cart-drawer-toggle>
                            <i class="bi bi-bag"></i>
                            <span class="store-cart-count" data-cart-count style="{{ $cartCount > 0 ? '' : 'display: none;' }}">{{ $cartCount }}</span>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
    </header>

    {{-- Flash Notifications --}}
    @if (session('success') || session('status') || session('error') || $errors->any())
        <div class="container mt-3">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-check-circle-fill text-success fs-5"></i>
                    <div>{{ session('success') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('status'))
                <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-info-circle-fill text-info fs-5"></i>
                    <div>{{ session('status') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm rounded-3" role="alert">
                    <i class="bi bi-exclamation-triangle-fill text-danger fs-5"></i>
                    <div>{{ session('error') }}</div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-3" role="alert">
                    <div class="fw-bold mb-1 d-flex align-items-center gap-2">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        Please address the following errors:
                    </div>
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>
    @endif

    {{-- Main Page Content --}}
    <main class="flex-grow-1">
        @yield('content')
    </main>

    {{-- High-Converting Modern Footer --}}
    <footer class="store-footer">
        <div class="container">
            <div class="row g-4 justify-content-between">
                <div class="col-lg-4 col-md-6">
                    <a class="store-brand text-white mb-3 d-inline-flex" href="{{ route('store.home') }}">
                        <span class="brand-badge bg-primary text-white"><i class="bi bi-bag-check-fill"></i></span>
                        <span>{{ config('app.name', 'MONO') }}</span>
                    </a>
                    <p class="text-secondary small pe-lg-4">
                        Curated collection of everyday essentials crafted with purpose, durability, and timeless modern style. Elevate your wardrobe and living space today.
                    </p>
                    <div class="d-flex align-items-center gap-3 mt-3">
                        <a href="#" class="text-secondary fs-5 hover-text-white"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="text-secondary fs-5 hover-text-white"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="text-secondary fs-5 hover-text-white"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="text-secondary fs-5 hover-text-white"><i class="bi bi-tiktok"></i></a>
                    </div>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6 class="store-footer-title">Navigation</h6>
                    <a href="{{ route('store.home') }}">Home</a>
                    <a href="{{ route('store.home') }}#featured-products">Shop All</a>
                    <a href="{{ route('store.home') }}#categories">Collections</a>
                    <a href="{{ route('store.cart.index') }}">My Cart</a>
                </div>

                <div class="col-lg-2 col-md-6 col-6">
                    <h6 class="store-footer-title">Customer Care</h6>
                    <a href="{{ route('store.account') }}">My Account</a>
                    <a href="#">Order Tracking</a>
                    <a href="#">Shipping Policy</a>
                    <a href="#">Returns & Exchanges</a>
                    <a href="#">Privacy Policy</a>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h6 class="store-footer-title">Stay Connected</h6>
                    <p class="text-secondary small">
                        Subscribe to receive special offers, new arrival previews, and exclusive editorial drops.
                    </p>
                    <form class="d-flex gap-2" onsubmit="event.preventDefault(); alert('Thank you for subscribing!');">
                        <input type="email" class="form-control form-control-sm border-secondary bg-dark text-white" placeholder="Enter your email" required>
                        <button type="submit" class="btn btn-store-accent btn-store-sm">Join</button>
                    </form>
                </div>
            </div>

            <div class="store-footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                <div class="small">
                    &copy; {{ date('Y') }} {{ config('app.name', 'MONO') }}. All rights reserved.
                </div>
                <div class="d-flex align-items-center gap-3 text-secondary small">
                    <span><i class="bi bi-shield-check text-success me-1"></i> Secure 256-bit SSL</span>
                    <span><i class="bi bi-credit-card me-1"></i> Cash on Delivery & Cards</span>
                </div>
            </div>
        </div>
    </footer>

    <div class="cart-drawer-backdrop" data-cart-drawer-backdrop aria-hidden="true"></div>
    <aside
        class="cart-drawer"
        id="cart-drawer"
        data-cart-drawer
        role="dialog"
        aria-modal="true"
        aria-hidden="true"
        aria-labelledby="cartDrawerTitle"
        data-drawer-url="{{ route('store.cart.drawer') }}"
    >
        <div class="cart-drawer-header">
            <div>
                <h2 id="cartDrawerTitle" class="h5 fw-bold mb-1">Your Bag</h2>
                <span class="small text-muted"><span data-cart-drawer-count>{{ $cartCount }}</span> items</span>
            </div>
            <button type="button" class="btn-close" data-cart-drawer-close aria-label="Close cart"></button>
        </div>
        <div class="cart-drawer-message" data-cart-drawer-message aria-live="polite"></div>
        <div class="cart-drawer-items" data-cart-drawer-items>
            <p class="text-muted small text-center py-4 mb-0">Your cart is ready.</p>
        </div>
        <div class="cart-drawer-footer">
            <div class="d-flex justify-content-between align-items-center fw-bold mb-3">
                <span>Subtotal</span>
                <span data-cart-drawer-subtotal>—</span>
            </div>
            <a href="{{ route('store.checkout.index') }}" class="btn btn-store-primary w-100 py-3 fw-bold">
                Checkout <i class="bi bi-arrow-right ms-2"></i>
            </a>
            <a href="{{ route('store.cart.index') }}" class="d-block text-center small text-muted mt-3">
                View full cart
            </a>
        </div>
    </aside>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        (() => {
            const drawer = document.querySelector('[data-cart-drawer]');
            const backdrop = document.querySelector('[data-cart-drawer-backdrop]');
            const itemsContainer = document.querySelector('[data-cart-drawer-items]');
            const message = document.querySelector('[data-cart-drawer-message]');
            const subtotal = document.querySelector('[data-cart-drawer-subtotal]');
            const count = document.querySelector('[data-cart-drawer-count]');

            if (!drawer || !backdrop) return;

            const updateDrawer = (data) => {
                itemsContainer.innerHTML = data.items_html;
                subtotal.textContent = `$${Number(data.subtotal).toFixed(2)}`;
                count.textContent = data.count;
                document.querySelectorAll('[data-cart-count]').forEach((badge) => {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'flex' : 'none';
                });
            };

            const showMessage = (text, isError = false) => {
                message.textContent = text;
                message.classList.toggle('is-error', isError);
                message.classList.toggle('is-visible', Boolean(text));
            };

            const openDrawer = async (refresh = true) => {
                drawer.classList.add('is-open');
                backdrop.classList.add('is-open');
                drawer.setAttribute('aria-hidden', 'false');
                backdrop.setAttribute('aria-hidden', 'false');
                document.body.classList.add('cart-drawer-open');
                drawer.querySelector('[data-cart-drawer-close]').focus();

                if (!refresh) return;

                try {
                    const response = await fetch(drawer.dataset.drawerUrl, {
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    if (!response.ok) throw new Error('Unable to load your cart. Please try again.');
                    updateDrawer(await response.json());
                    showMessage('');
                } catch (error) {
                    showMessage(error.message, true);
                }
            };

            const closeDrawer = () => {
                drawer.classList.remove('is-open');
                backdrop.classList.remove('is-open');
                drawer.setAttribute('aria-hidden', 'true');
                backdrop.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('cart-drawer-open');
            };

            document.querySelectorAll('[data-cart-drawer-toggle]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    event.preventDefault();
                    openDrawer();
                });
            });
            document.querySelectorAll('[data-cart-drawer-close]').forEach((button) => {
                button.addEventListener('click', closeDrawer);
            });
            backdrop.addEventListener('click', closeDrawer);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && drawer.classList.contains('is-open')) closeDrawer();
            });

            document.addEventListener('submit', async (event) => {
                const removeForm = event.target.closest('form[data-cart-remove]');

                if (removeForm) {
                    event.preventDefault();
                    const button = removeForm.querySelector('[type="submit"]');
                    if (button) button.disabled = true;

                    try {
                        const response = await fetch(removeForm.action, {
                            method: removeForm.method || 'POST',
                            body: new FormData(removeForm),
                            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        const data = await response.json();
                        if (!response.ok) {
                            throw new Error(data.message || 'Unable to remove this item from your cart.');
                        }

                        updateDrawer(data);
                        showMessage(data.message || 'Item removed from your cart.');
                    } catch (error) {
                        showMessage(error.message, true);
                        if (button) button.disabled = false;
                    }

                    return;
                }

                const form = event.target.closest('form[data-cart-add]');
                if (!form) return;

                event.preventDefault();
                if (!form.reportValidity()) return;

                const button = form.querySelector('[type="submit"]');
                if (button) button.disabled = true;

                try {
                    const response = await fetch(form.action, {
                        method: form.method || 'POST',
                        body: new FormData(form),
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    });
                    const data = await response.json();
                    if (!response.ok) {
                        const validationMessage = data.errors
                            ? Object.values(data.errors).flat()[0]
                            : data.message;
                        throw new Error(validationMessage || 'Unable to add this item to your cart.');
                    }

                    updateDrawer(data);
                    form.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
                    showMessage(data.message || 'Added to your cart.');
                    await openDrawer(false);
                } catch (error) {
                    showMessage(error.message, true);
                    await openDrawer(false);
                } finally {
                    if (button) {
                        const selectedVariant = form.querySelector('[data-selected-variant]');
                        button.disabled = selectedVariant
                            ? form.dataset.variantUnavailable === 'true'
                            : false;
                    }
                }
            });

            // ==========================================
            // Live Instant Search Autocomplete
            // ==========================================
            const searchForms = document.querySelectorAll('form[data-live-search]');
            searchForms.forEach((form) => {
                const input = form.querySelector('[data-search-input]');
                const clearBtn = form.querySelector('[data-search-clear]');
                const spinner = form.querySelector('[data-search-spinner]');
                const dropdown = form.querySelector('[data-search-dropdown]');
                const categoriesContainer = form.querySelector('[data-search-categories]');
                const resultsContainer = form.querySelector('[data-search-results]');
                const emptyContainer = form.querySelector('[data-search-empty]');
                const emptyTerm = form.querySelector('[data-search-empty-term]');
                const footerContainer = form.querySelector('[data-search-footer]');
                const viewAllLink = form.querySelector('[data-search-view-all]');

                if (!input || !dropdown) return;

                let debounceTimer = null;
                let activeAbortController = null;
                let selectedIndex = -1;

                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str || '';
                    return div.innerHTML;
                }

                function highlightTerm(text, query) {
                    if (!query) return escapeHtml(text);
                    const escapedText = escapeHtml(text);
                    const escapedQuery = escapeHtml(query).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                    const regex = new RegExp(`(${escapedQuery})`, 'gi');
                    return escapedText.replace(regex, '<span class="text-primary fw-bold">$1</span>');
                }

                function showLoading(show) {
                    if (spinner) spinner.classList.toggle('d-none', !show);
                    if (clearBtn) clearBtn.classList.toggle('d-none', show || !input.value.trim());
                }

                function closeDropdown() {
                    dropdown.classList.add('d-none');
                    selectedIndex = -1;
                }

                function openDropdown() {
                    dropdown.classList.remove('d-none');
                }

                function updateSelection(items) {
                    items.forEach((item, index) => {
                        item.classList.toggle('is-selected', index === selectedIndex);
                    });
                    if (selectedIndex >= 0 && items[selectedIndex]) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                }

                async function performSearch(query) {
                    query = query.trim();
                    if (query.length < 2) {
                        closeDropdown();
                        showLoading(false);
                        return;
                    }

                    if (activeAbortController) {
                        activeAbortController.abort();
                    }
                    activeAbortController = new AbortController();

                    showLoading(true);

                    try {
                        const url = new URL('{{ route('store.search.suggestions') }}', window.location.origin);
                        url.searchParams.set('q', query);

                        const response = await fetch(url.toString(), {
                            signal: activeAbortController.signal,
                            headers: { Accept: 'application/json' }
                        });

                        if (!response.ok) throw new Error('Search failed');
                        const data = await response.json();

                        renderResults(data, query);
                    } catch (err) {
                        if (err.name !== 'AbortError') {
                            console.error('Live search error:', err);
                        }
                    } finally {
                        showLoading(false);
                    }
                }

                function renderResults(data, query) {
                    const results = data.results || [];
                    const categories = data.categories || [];
                    const total = data.total || 0;

                    selectedIndex = -1;

                    // Render Category Chips
                    if (categories.length > 0 && categoriesContainer) {
                        categoriesContainer.innerHTML = `
                            <span class="store-search-categories-label">In Categories:</span>
                            ${categories.map(c => `
                                <a href="${escapeHtml(c.url)}" class="store-search-cat-chip">
                                    <i class="bi bi-tag-fill text-muted" style="font-size: 10px;"></i>
                                    <span>${highlightTerm(c.name, query)}</span>
                                </a>
                            `).join('')}
                        `;
                        categoriesContainer.classList.remove('d-none');
                    } else if (categoriesContainer) {
                        categoriesContainer.innerHTML = '';
                        categoriesContainer.classList.add('d-none');
                    }

                    // Render Product Results
                    if (results.length > 0) {
                        if (emptyContainer) emptyContainer.classList.add('d-none');
                        resultsContainer.innerHTML = results.map(p => `
                            <a href="${escapeHtml(p.url)}" class="store-search-item" data-search-result-item>
                                ${p.image 
                                    ? `<img src="${escapeHtml(p.image)}" alt="" class="store-search-thumb" loading="lazy">` 
                                    : `<div class="store-search-thumb-placeholder"><i class="bi bi-box"></i></div>`
                                }
                                <div class="store-search-item-info">
                                    <div class="store-search-item-title">${highlightTerm(p.name, query)}</div>
                                    <div class="store-search-item-meta">
                                        ${p.category ? `<span>${escapeHtml(p.category)}</span>` : ''}
                                        ${!p.in_stock ? `<span class="badge bg-secondary-subtle text-secondary ms-1">Sold out</span>` : ''}
                                    </div>
                                </div>
                                <div class="store-search-item-price">
                                    <div>${escapeHtml(p.price_formatted)}</div>
                                    ${p.on_sale && p.compare_at_price_formatted ? `<div class="store-search-item-compare">${escapeHtml(p.compare_at_price_formatted)}</div>` : ''}
                                </div>
                            </a>
                        `).join('');

                        if (footerContainer && viewAllLink) {
                            viewAllLink.href = data.view_all_url || `{{ route('store.home') }}?search=${encodeURIComponent(query)}`;
                            viewAllLink.innerHTML = `See all ${total} results for "${escapeHtml(query)}" <i class="bi bi-arrow-right ms-1"></i>`;
                            footerContainer.classList.remove('d-none');
                        }

                        openDropdown();
                    } else {
                        resultsContainer.innerHTML = '';
                        if (footerContainer) footerContainer.classList.add('d-none');
                        if (emptyContainer && emptyTerm) {
                            emptyTerm.textContent = query;
                            emptyContainer.classList.remove('d-none');
                            openDropdown();
                        } else {
                            closeDropdown();
                        }
                    }
                }

                // Input Typing
                input.addEventListener('input', function() {
                    const val = input.value.trim();
                    if (clearBtn) clearBtn.classList.toggle('d-none', !val);

                    clearTimeout(debounceTimer);
                    if (val.length < 2) {
                        closeDropdown();
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        performSearch(val);
                    }, 220);
                });

                // Clear Button Click
                if (clearBtn) {
                    clearBtn.addEventListener('click', function() {
                        input.value = '';
                        clearBtn.classList.add('d-none');
                        closeDropdown();
                        input.focus();
                    });
                }

                // Re-open on focus if query present
                input.addEventListener('focus', function() {
                    if (input.value.trim().length >= 2 && resultsContainer.children.length > 0) {
                        openDropdown();
                    }
                });

                // Keyboard Navigation
                input.addEventListener('keydown', function(e) {
                    const items = dropdown.querySelectorAll('[data-search-result-item]');
                    if (!items.length || dropdown.classList.contains('d-none')) {
                        if (e.key === 'Escape') closeDropdown();
                        return;
                    }

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        selectedIndex = (selectedIndex + 1) % items.length;
                        updateSelection(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        selectedIndex = selectedIndex <= 0 ? items.length - 1 : selectedIndex - 1;
                        updateSelection(items);
                    } else if (e.key === 'Enter') {
                        if (selectedIndex >= 0 && items[selectedIndex]) {
                            e.preventDefault();
                            items[selectedIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        closeDropdown();
                    }
                });

                // Close on Outside Click
                document.addEventListener('click', function(e) {
                    if (!form.contains(e.target)) {
                        closeDropdown();
                    }
                });
            });
        })();
    </script>

    @stack('scripts')
</body>
</html>
