@extends('layouts.store')

@section('title', config('app.name', 'MONO') . ' — Timeless Style & Curated Essentials')

@section('content')
    {{-- Hero Section --}}
    <section class="hero-section py-5">
        <div class="container py-lg-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="hero-badge">
                        <i class="bi bi-stars text-primary"></i>
                        <span>Fall / Winter '26 Collection Now Live</span>
                    </div>

                    <h1 class="hero-title">
                        Timeless style.<br>
                        <span class="highlight">Effortless living.</span>
                    </h1>

                    <p class="hero-lead">
                        Discover thoughtfully selected essentials crafted to bring enduring style and practical luxury to your everyday life.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <a href="#featured-products" class="btn btn-store-primary btn-store-lg">
                            <span>Explore Catalog</span>
                            <i class="bi bi-arrow-down-short fs-5"></i>
                        </a>
                        <a href="#categories" class="btn btn-store-outline btn-store-lg">
                            Browse Collections
                        </a>
                    </div>

                    <div class="d-flex align-items-center gap-4 mt-4 pt-3 text-secondary small">
                        <div><strong class="text-dark fs-5">1,200+</strong><br>Curated Pieces</div>
                        <div class="border-start ps-4"><strong class="text-dark fs-5">99.8%</strong><br>Happy Customers</div>
                        <div class="border-start ps-4"><strong class="text-dark fs-5">Fast</strong><br>Express Shipping</div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="hero-image-wrapper">
                        <img
                            src="https://images.unsplash.com/photo-1441986300917-64674bd600d8?auto=format&fit=crop&w=1200&q=85"
                            alt="Contemporary boutique curated collection"
                        >
                        <div class="hero-floating-card">
                            <div class="bg-primary text-white rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="bi bi-award-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark small">Guaranteed Craftsmanship</h6>
                                <span class="text-muted small">Ethically sourced materials</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Value Propositions Strip --}}
    <section class="features-strip">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon-box">
                            <i class="bi bi-truck"></i>
                        </div>
                        <div class="feature-text">
                            <h6>Free Fast Delivery</h6>
                            <p>On all orders over $75</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon-box">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <div class="feature-text">
                            <h6>Secure Payments</h6>
                            <p>Encrypted transactions</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon-box">
                            <i class="bi bi-arrow-repeat"></i>
                        </div>
                        <div class="feature-text">
                            <h6>30-Day Returns</h6>
                            <p>No questions asked</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="feature-item">
                        <div class="feature-icon-box">
                            <i class="bi bi-headset"></i>
                        </div>
                        <div class="feature-text">
                            <h6>24/7 Concierge</h6>
                            <p>Dedicated customer care</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Categories Showcase Strip --}}
    @if ($categories->isNotEmpty())
        <section id="categories" class="py-5 bg-white border-bottom">
            <div class="container">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-4">
                    <div>
                        <span class="text-primary fw-bold text-uppercase small tracking-wide">Collections</span>
                        <h2 class="fw-bold text-dark mb-0">Browse by Category</h2>
                    </div>
                    <a href="{{ route('store.home') }}#featured-products" class="btn btn-store-outline btn-store-sm align-self-start align-self-md-auto">
                        View All Items <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>

                @php
                    $selectedCatId = request('category_id');
                    $activeParent = null;
                    if ($selectedCatId) {
                        foreach ($categories as $cat) {
                            if ($cat->id == $selectedCatId || $cat->children->contains('id', $selectedCatId)) {
                                $activeParent = $cat;
                                break;
                            }
                        }
                    }
                @endphp

                <div class="category-scroll-container pb-2">
                    <a href="{{ route('store.home') }}" class="category-pill {{ !request('category_id') ? 'active' : '' }}">
                        <i class="bi bi-grid-fill"></i> All Items
                    </a>
                    @foreach ($categories as $cat)
                        @php
                            $isCatActive = $selectedCatId == $cat->id;
                            $isCatParentActive = $activeParent && $activeParent->id == $cat->id;
                        @endphp
                        <a href="{{ route('store.home', array_merge(request()->except('page'), ['category_id' => $cat->id])) }}#featured-products" class="category-pill {{ $isCatActive || $isCatParentActive ? 'active' : '' }}">
                            <span>{{ $cat->name }}</span>
                            @if ($cat->total_products_count > 0)
                                <span class="badge {{ $isCatActive || $isCatParentActive ? 'bg-white text-dark' : 'bg-secondary' }} rounded-pill ms-1">{{ $cat->total_products_count }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>

                @if ($activeParent && $activeParent->children->isNotEmpty())
                    <div class="mt-3 pt-3 border-top d-flex flex-wrap align-items-center gap-2">
                        <span class="small text-muted fw-bold me-1 text-uppercase" style="font-size: 11px; letter-spacing: 0.5px;">
                            <i class="bi bi-diagram-3 me-1"></i> {{ $activeParent->name }} Subcategories:
                        </span>
                        <a href="{{ route('store.home', array_merge(request()->except('page'), ['category_id' => $activeParent->id])) }}#featured-products"
                           class="badge rounded-pill px-3 py-2 text-decoration-none fw-semibold {{ $selectedCatId == $activeParent->id ? 'bg-dark text-white shadow-xs' : 'bg-light text-dark border' }}">
                            All {{ $activeParent->name }} ({{ $activeParent->total_products_count }})
                        </a>
                        @foreach ($activeParent->children as $subcat)
                            <a href="{{ route('store.home', array_merge(request()->except('page'), ['category_id' => $subcat->id])) }}#featured-products"
                               class="badge rounded-pill px-3 py-2 text-decoration-none fw-semibold {{ $selectedCatId == $subcat->id ? 'bg-primary text-white shadow-xs' : 'bg-light text-dark border' }}">
                                {{ $subcat->name }} ({{ $subcat->products_count }})
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>
    @endif

    {{-- Curated New Arrivals (if available and no search filter applied) --}}
    @if ($newArrivals->isNotEmpty() && !request()->hasAny(['search', 'category_id', 'min_price', 'max_price']))
        <section class="py-5 bg-light border-bottom">
            <div class="container">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="badge bg-primary text-white rounded-pill px-3 py-1 mb-2 small fw-bold">Fresh Drops</span>
                        <h3 class="fw-bold text-dark mb-0">New Arrivals</h3>
                    </div>
                    <a href="#featured-products" class="text-secondary small fw-semibold text-decoration-none">
                        Browse Full Catalog <i class="bi bi-arrow-down-short"></i>
                    </a>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                    @foreach ($newArrivals as $item)
                        <div class="col">
                            <div class="product-card">
                                <a href="{{ route('store.products.show', $item->slug) }}" class="product-card-img-wrapper">
                                    @if ($item->image)
                                        <img src="{{ Str::startsWith($item->image, ['http://', 'https://']) ? $item->image : asset('storage/' . $item->image) }}" alt="{{ $item->name }}" class="product-card-img" loading="lazy">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                            <i class="bi bi-image fs-1"></i>
                                        </div>
                                    @endif

                                    <div class="product-card-badges">
                                        <span class="product-badge badge-new">New</span>
                                        @if ($item->compare_at_price && $item->compare_at_price > $item->price)
                                            <span class="product-badge badge-sale">Sale</span>
                                        @endif
                                    </div>

                                    @php
                                        $inWishlist = auth()->check()
                                            ? auth()->user()->wishlists->contains('product_id', $item->id)
                                            : in_array($item->id, (array) session('wishlist', []));
                                    @endphp
                                    <form method="POST" action="{{ route('store.wishlist.toggle', $item) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-card-wishlist {{ $inWishlist ? 'active' : '' }}" title="{{ $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                            <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}"></i>
                                        </button>
                                    </form>
                                </a>

                                <div class="product-card-body">
                                    <div class="product-card-category">
                                        {{ $item->category->name ?? 'Collection' }}
                                    </div>
                                    <h6 class="product-card-title">
                                        <a href="{{ route('store.products.show', $item->slug) }}">
                                            {{ $item->name }}
                                        </a>
                                    </h6>

                                    <div class="d-flex align-items-center gap-1 mb-2">
                                        <span class="star-rating">
                                            @php $avgRating = $item->average_rating; @endphp
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star-fill {{ $i <= round($avgRating) ? '' : 'star-muted' }}" style="font-size: 11px;"></i>
                                            @endfor
                                        </span>
                                        <span class="text-muted small" style="font-size: 11px;">
                                            @if ($item->reviews_count > 0)
                                                {{ number_format($avgRating, 1) }} ({{ $item->reviews_count }})
                                            @else
                                                New
                                            @endif
                                        </span>
                                    </div>
                                    <div class="product-card-footer">
                                        <div class="product-price-box">
                                            <span class="product-price">${{ number_format($item->price, 2) }}</span>
                                            @if ($item->compare_at_price && $item->compare_at_price > $item->price)
                                                <span class="product-compare-price">${{ number_format($item->compare_at_price, 2) }}</span>
                                            @endif
                                        </div>
                                        @if ($item->activeVariants->isNotEmpty())
                                            <a href="{{ route('store.products.show', $item->slug) }}" class="btn-card-add" title="Choose product options">
                                                <i class="bi bi-sliders"></i>
                                            </a>
                                        @else
                                            <form method="POST" action="{{ route('store.cart.add', $item) }}" data-cart-add>
                                                @csrf
                                                <input type="hidden" name="quantity" value="1">
                                                <button type="submit" class="btn-card-add" title="Add to cart">
                                                    <i class="bi bi-bag-plus"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Main Catalog & Filter Section --}}
    <section id="featured-products" class="py-5">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        @if (request('search'))
                            Search Results for "{{ request('search') }}"
                        @elseif(request('category_id'))
                            @php
                                $currentCategory = \App\Models\Category::with('parent')->find(request('category_id'));
                            @endphp
                            @if ($currentCategory?->parent)
                                {{ $currentCategory->parent->name }} › {{ $currentCategory->name }}
                            @else
                                {{ $currentCategory->name ?? 'Category' }} Collection
                            @endif
                        @else
                            Curated Collection
                        @endif
                    </h2>
                    <p class="text-muted small mb-0">Showing {{ $products->total() }} premium items</p>
                </div>

                {{-- Filter & Sort Form --}}
                <form method="GET" action="{{ route('store.home') }}#featured-products" class="d-flex flex-wrap align-items-center gap-2">
                    @if (request('category_id'))
                        <input type="hidden" name="category_id" value="{{ request('category_id') }}">
                    @endif
                    @if (request('search'))
                        <input type="hidden" name="search" value="{{ request('search') }}">
                    @endif

                    <div class="input-group input-group-sm" style="max-width: 220px;">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-arrow-down-up"></i></span>
                        <select name="sort" class="form-select form-select-sm border-start-0" onchange="this.form.submit()">
                            <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Latest Arrivals</option>
                            <option value="price_low" {{ request('sort') == 'price_low' ? 'selected' : '' }}>Price: Low to High</option>
                            <option value="price_high" {{ request('sort') == 'price_high' ? 'selected' : '' }}>Price: High to Low</option>
                            <option value="name" {{ request('sort') == 'name' ? 'selected' : '' }}>Alphabetical (A-Z)</option>
                        </select>
                    </div>

                    @if (request()->hasAny(['search', 'category_id', 'sort', 'min_price', 'max_price']))
                        <a href="{{ route('store.home') }}" class="btn btn-store-outline btn-store-sm" title="Clear all filters">
                            <i class="bi bi-x-circle me-1"></i> Reset Filters
                        </a>
                    @endif
                </form>
            </div>

            {{-- Products Grid --}}
            @if ($products->isEmpty())
                <div class="card border-0 shadow-sm rounded-4 text-center py-5 my-4">
                    <div class="py-4">
                        <i class="bi bi-search fs-1 text-muted opacity-50 mb-3 d-block"></i>
                        <h4 class="fw-bold text-dark mb-1">No products found</h4>
                        <p class="text-muted small mb-4">Try adjusting your search criteria or resetting filters to see available pieces.</p>
                        <a href="{{ route('store.home') }}" class="btn btn-store-primary">
                            Reset & View All Products
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

                                    <div class="product-card-badges">
                                        @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                                            @php
                                                $savePercent = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                            @endphp
                                            <span class="product-badge badge-sale">-{{ $savePercent }}%</span>
                                        @endif
                                        @if ($product->available_stock < 1)
                                            <span class="product-badge badge-out-of-stock">Sold Out</span>
                                        @endif
                                    </div>

                                    @php
                                        $inWishlist = auth()->check()
                                            ? auth()->user()->wishlists->contains('product_id', $product->id)
                                            : in_array($product->id, (array) session('wishlist', []));
                                    @endphp
                                    <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn-card-wishlist {{ $inWishlist ? 'active' : '' }}" title="{{ $inWishlist ? 'Remove from wishlist' : 'Add to wishlist' }}">
                                            <i class="bi {{ $inWishlist ? 'bi-heart-fill' : 'bi-heart' }}"></i>
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

                                    <div class="d-flex align-items-center gap-1 mb-2">
                                        <span class="star-rating">
                                            @php $avgRating = $product->average_rating; @endphp
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star-fill {{ $i <= round($avgRating) ? '' : 'star-muted' }}" style="font-size: 11px;"></i>
                                            @endfor
                                        </span>
                                        <span class="text-muted small" style="font-size: 11px;">
                                            @if ($product->reviews_count > 0)
                                                {{ number_format($avgRating, 1) }} ({{ $product->reviews_count }})
                                            @else
                                                New
                                            @endif
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
                                                <a href="{{ route('store.products.show', $product->slug) }}" class="btn-card-add" title="Choose product options">
                                                    <i class="bi bi-sliders"></i>
                                                </a>
                                            @else
                                                <form method="POST" action="{{ route('store.cart.add', $product) }}" data-cart-add>
                                                    @csrf
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn-card-add" title="Quick Add to Cart">
                                                        <i class="bi bi-bag-plus"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <button class="btn-card-add disabled opacity-50" disabled title="Out of Stock">
                                                <i class="bi bi-x"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination Links --}}
                <div class="d-flex justify-content-center mt-4">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
            @endif
        </div>
    </section>

    {{-- Editorial / Story Banner --}}
    <section class="py-5 bg-white border-top">
        <div class="container">
            <div class="p-5 rounded-4 bg-light border text-center" style="background: linear-gradient(135deg, #f8fafc 0%, #edf2f7 100%);">
                <span class="badge bg-white text-primary border rounded-pill px-3 py-1 mb-3 small fw-bold">The Ethos</span>
                <h2 class="fw-bold text-dark mb-3">Designed for the Long Run</h2>
                <p class="text-muted mx-auto mb-4" style="max-width: 620px;">
                    Every single item in our collection is rigorously inspected for durability, aesthetic relevance, and everyday comfort. We believe in buying fewer, higher quality things.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="#featured-products" class="btn btn-store-primary">Shop Our Curations</a>
                </div>
            </div>
        </div>
    </section>
@endsection
