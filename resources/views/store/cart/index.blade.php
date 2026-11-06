@extends('layouts.store')

@section('title', 'Shopping Cart | ' . config('app.name', 'MONO'))

@section('content')
<div class="container py-5">
    {{-- Breadcrumb & Title --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('store.home') }}" class="text-secondary">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Shopping Cart</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-baseline mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1">Your Shopping Cart</h2>
            <p class="text-muted small mb-0">Review selected items before proceeding to secure checkout</p>
        </div>
        @if (count($items) > 0)
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ count($items) }} {{ Str::plural('item', count($items)) }}
            </span>
        @endif
    </div>

    @if (empty($items))
        {{-- Empty Cart State --}}
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
            <div class="py-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-bag-x fs-1"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Your cart is currently empty</h4>
                <p class="text-muted small mb-4" style="max-width: 440px; margin: 0 auto;">
                    Looks like you haven't added any essentials to your cart yet. Explore our curated catalog to find what you love.
                </p>
                <a href="{{ route('store.home') }}#featured-products" class="btn btn-store-primary btn-store-lg">
                    <i class="bi bi-arrow-left me-2"></i> Start Shopping Now
                </a>
            </div>
        </div>
    @else
        {{-- Cart Layout --}}
        <div class="row g-4 align-items-start">
            {{-- Items List --}}
            <div class="col-lg-8">
                <div class="cart-table-card">
                    <div class="cart-card-header d-flex justify-content-between align-items-center">
                        <h5>Cart Items</h5>
                        <a href="{{ route('store.home') }}#featured-products" class="small text-secondary text-decoration-none">
                            <i class="bi bi-plus-lg me-1"></i> Add more items
                        </a>
                    </div>

                    <div class="card-body p-0">
                        @foreach ($items as $item)
                            @php
                                $product = $item['product'];
                                $variant = $item['variant'];
                                $quantity = $item['quantity'];
                                $subtotal = $item['subtotal'];
                                $unitPrice = $item['unit_price'];
                            @endphp
                            <div class="cart-item-row flex-column flex-sm-row align-items-start align-items-sm-center">
                                {{-- Product Image --}}
                                <a href="{{ route('store.products.show', $product->slug) }}">
                                    @if ($product->image)
                                        <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="cart-item-thumb">
                                    @else
                                        <div class="cart-item-thumb d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-3"></i>
                                        </div>
                                    @endif
                                </a>

                                {{-- Product Details --}}
                                <div class="flex-grow-1">
                                    <div class="small text-muted text-uppercase fw-semibold mb-1" style="font-size: 11px;">
                                        {{ $product->category->name ?? 'Collection' }}
                                    </div>
                                    <h6 class="fw-bold text-dark mb-1">
                                        <a href="{{ route('store.products.show', $product->slug) }}" class="text-dark hover-text-primary">
                                            {{ $product->name }}
                                        </a>
                                    </h6>
                                    <div class="text-muted small">
                                        @if ($variant)
                                            <div>{{ $variant->display_name }}</div>
                                        @endif
                                        Unit Price: <span class="fw-semibold text-dark">${{ number_format($unitPrice, 2) }}</span>
                                        @unless ($item['is_available'])
                                            <div class="text-danger fw-semibold mt-1">This option is no longer available in the requested quantity.</div>
                                        @endunless
                                    </div>
                                </div>

                                {{-- Quantity Adjuster Form --}}
                                <div class="d-flex align-items-center gap-3 mt-3 mt-sm-0">
                                    <form method="POST" action="{{ route('store.cart.update', $product) }}" class="d-flex align-items-center">
                                        @csrf
                                        @method('PATCH')
                                        @if ($item['variant_id'])
                                            <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                                        @endif
                                        <div class="quantity-picker shadow-xs">
                                            <button type="button" class="quantity-btn" onclick="let input = this.nextElementSibling; if(parseInt(input.value) > 1) { input.value = parseInt(input.value) - 1; this.form.submit(); }">
                                                <i class="bi bi-dash"></i>
                                            </button>
                                            <input type="number" name="quantity" class="quantity-input" value="{{ $quantity }}" min="1" max="{{ $item['stock'] }}" readonly>
                                            <button type="button" class="quantity-btn" onclick="let input = this.previousElementSibling; if(parseInt(input.value) < {{ $item['stock'] }}) { input.value = parseInt(input.value) + 1; this.form.submit(); }">
                                                <i class="bi bi-plus"></i>
                                            </button>
                                        </div>
                                    </form>

                                    {{-- Subtotal & Remove --}}
                                    <div class="text-end" style="min-width: 90px;">
                                        <div class="fw-bold text-dark fs-6">${{ number_format($subtotal, 2) }}</div>
                                        <form method="POST" action="{{ route('store.cart.remove', $product) }}" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            @if ($item['variant_id'])
                                                <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                                            @endif
                                            <button type="submit" class="btn btn-link text-danger p-0 border-0 small text-decoration-none mt-1" title="Remove item">
                                                <i class="bi bi-trash3 me-1"></i> Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Shopping Perks Box --}}
                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-truck fs-4 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-0 small text-dark">Fast Dispatch</h6>
                                <span class="text-muted small">Ships within 24-48 hrs</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-arrow-repeat fs-4 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-0 small text-dark">Easy Returns</h6>
                                <span class="text-muted small">30 days return window</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 bg-white border rounded-3 d-flex align-items-center gap-3">
                            <i class="bi bi-shield-check fs-4 text-primary"></i>
                            <div>
                                <h6 class="fw-bold mb-0 small text-dark">Secure Guarantee</h6>
                                <span class="text-muted small">Encrypted checkout</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Summary Sidebar --}}
            <div class="col-lg-4">
                <div class="cart-summary-box">
                    <h5 class="fw-bold text-dark mb-3">Order Summary</h5>

                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <span class="fw-semibold text-dark">${{ number_format($total, 2) }}</span>
                    </div>

                    <div class="summary-row">
                        <span>Estimated Shipping</span>
                        @if ($total >= 75)
                            <span class="fw-bold text-success">FREE</span>
                        @else
                            <span class="fw-semibold text-dark">$10.00</span>
                        @endif
                    </div>

                    @if ($total < 75)
                        <div class="bg-light p-2 rounded-2 small text-muted mb-2">
                            <i class="bi bi-info-circle text-primary me-1"></i> Add <strong>${{ number_format(75 - $total, 2) }}</strong> more to unlock <strong>Free Shipping</strong>!
                        </div>
                    @endif

                    @php
                        $estimatedTotal = $total >= 75 ? $total : $total + 10;
                    @endphp

                    <div class="summary-row total-row">
                        <span>Estimated Total</span>
                        <span>${{ number_format($estimatedTotal, 2) }}</span>
                    </div>

                    <div class="mt-4">
                        @if (collect($items)->every(fn ($item) => $item['is_available']))
                            <a href="{{ route('store.checkout.index') }}" class="btn btn-store-primary w-100 py-3 fw-bold fs-6">
                                Proceed to Checkout <i class="bi bi-arrow-right ms-2"></i>
                            </a>
                        @else
                            <div class="alert alert-warning small mb-0">Remove unavailable items or reduce their quantity before checkout.</div>
                        @endif
                    </div>

                    <div class="text-center mt-3">
                        <a href="{{ route('store.home') }}" class="small text-muted text-decoration-none">
                            <i class="bi bi-arrow-left me-1"></i> Continue browsing products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
