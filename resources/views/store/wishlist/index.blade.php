@extends('layouts.store')

@section('title', 'My Saved Wishlist | ' . config('app.name', 'MONO'))

@section('content')
<div class="container py-5">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('store.home') }}" class="text-secondary">Home</a></li>
            <li class="breadcrumb-item active" aria-current="page">Wishlist</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-baseline mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">My Saved Wishlist</h2>
            <p class="text-muted small mb-0">Curated pieces you've saved for future styling</p>
        </div>
        @if ($products->isNotEmpty())
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ $products->count() }} {{ Str::plural('saved item', $products->count()) }}
            </span>
        @endif
    </div>

    @if ($products->isEmpty())
        <div class="card border-0 shadow-sm rounded-4 text-center py-5 px-3">
            <div class="py-4">
                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center text-muted mb-3" style="width: 80px; height: 80px;">
                    <i class="bi bi-heart fs-1 text-danger opacity-50"></i>
                </div>
                <h4 class="fw-bold text-dark mb-2">Your wishlist is currently empty</h4>
                <p class="text-muted small mb-4" style="max-width: 440px; margin: 0 auto;">
                    Tap the heart icon on any product in our catalog to save your favorite pieces and keep track of stock availability.
                </p>
                <a href="{{ route('store.home') }}#featured-products" class="btn btn-store-primary btn-store-lg">
                    <i class="bi bi-arrow-left me-2"></i> Browse Curated Products
                </a>
            </div>
        </div>
    @else
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4 mb-5">
            @foreach ($products as $product)
                <div class="col">
                    <div class="product-card">
                        <a href="{{ route('store.products.show', $product->slug) }}" class="product-card-img-wrapper">
                            @if ($product->image)
                                <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="product-card-img" loading="lazy">
                            @else
                                <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                    <i class="bi bi-image fs-1"></i>
                                </div>
                            @endif

                            {{-- Remove from Wishlist Button Overlay --}}
                            <form method="POST" action="{{ route('store.wishlist.remove', $product) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-card-wishlist active" title="Remove from wishlist">
                                    <i class="bi bi-heart-fill"></i>
                                </button>
                            </form>
                        </a>

                        <div class="product-card-body">
                            <div class="product-card-category">
                                {{ $product->category->name ?? 'Collection' }}
                            </div>

                            <h6 class="product-card-title">
                                <a href="{{ route('store.products.show', $product->slug) }}">
                                    {{ $product->name }}
                                </a>
                            </h6>

                            {{-- Rating Preview --}}
                            <div class="d-flex align-items-center gap-1 mb-2">
                                <span class="star-rating">
                                    @php
                                        $avg = $product->average_rating;
                                    @endphp
                                    @for ($i = 1; $i <= 5; $i++)
                                        <i class="bi bi-star-fill {{ $i <= round($avg) ? '' : 'star-muted' }}"></i>
                                    @endfor
                                </span>
                                <span class="text-muted small" style="font-size: 11px;">
                                    ({{ $product->reviews_count }})
                                </span>
                            </div>

                            <div class="product-card-footer">
                                <div class="product-price-box">
                                    <span class="product-price">${{ number_format($product->price, 2) }}</span>
                                    @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                                        <span class="product-compare-price">${{ number_format($product->compare_at_price, 2) }}</span>
                                    @endif
                                </div>

                                @if ($product->available_stock > 0)
                                    @if ($product->activeVariants->isNotEmpty())
                                        <a href="{{ route('store.products.show', $product->slug) }}" class="btn btn-store-primary btn-store-sm">
                                            <i class="bi bi-sliders me-1"></i> Choose options
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('store.cart.add', $product) }}" data-cart-add>
                                            @csrf
                                            <input type="hidden" name="quantity" value="1">
                                            <button type="submit" class="btn btn-store-primary btn-store-sm" title="Move to cart">
                                                <i class="bi bi-bag-plus me-1"></i> Add
                                            </button>
                                        </form>
                                    @endif
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary small">Out of stock</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
