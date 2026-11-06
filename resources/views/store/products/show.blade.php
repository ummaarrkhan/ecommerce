@extends('layouts.store')

@section('title', $product->name . ' | ' . config('app.name', 'MONO'))

@section('content')
<div class="container py-5">
    @php
        $galleryImages = $product->all_images;
        $activeVariants = $product->activeVariants;
        $availableStock = $product->available_stock;
        $variantSizes = $activeVariants->pluck('size')->filter()->unique()->values();
        $variantColors = $activeVariants->pluck('color')->filter()->unique()->values();
    @endphp

    {{-- Breadcrumbs --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('store.home') }}" class="text-secondary">Home</a></li>
            @if ($product->category)
                <li class="breadcrumb-item">
                    <a href="{{ route('store.home', ['category_id' => $product->category->id]) }}" class="text-secondary">
                        {{ $product->category->name }}
                    </a>
                </li>
            @endif
            <li class="breadcrumb-item active text-dark fw-medium" aria-current="page">{{ $product->name }}</li>
        </ol>
    </nav>

    {{-- Main Product Card --}}
    <div class="product-detail-wrapper mb-5">
        <div class="row g-5 align-items-center">
            {{-- Image Gallery Column --}}
            <div class="col-lg-6">
                <div class="position-relative">
                    <div class="product-gallery-main">
                        @if ($galleryImages->isNotEmpty())
                            <img src="{{ $galleryImages->first()['url'] }}" alt="{{ $product->name }}" data-gallery-main>
                        @else
                            <div class="w-100 h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                                <i class="bi bi-image fs-1 mb-2"></i>
                                <span class="small">No image uploaded</span>
                            </div>
                        @endif

                        {{-- Badges inside gallery container --}}
                        <div class="position-absolute top-0 start-0 m-3 d-flex flex-column gap-2" style="z-index: 3;">
                            @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                                @php
                                    $savings = round((($product->compare_at_price - $product->price) / $product->compare_at_price) * 100);
                                @endphp
                                <span class="badge bg-danger rounded-pill px-3 py-2 fw-bold shadow-xs">
                                    SAVE {{ $savings }}%
                                </span>
                            @endif

                            @if ($availableStock < 1)
                                <span class="badge bg-secondary rounded-pill px-3 py-2 fw-bold shadow-xs">
                                    Sold Out
                                </span>
                            @endif
                        </div>
                    </div>

                    @if ($galleryImages->count() > 1)
                        <div class="product-gallery-thumbnails d-flex gap-2 mt-3" data-product-gallery>
                            @foreach ($galleryImages as $galleryImage)
                                <button
                                    type="button"
                                    class="product-gallery-thumbnail {{ $loop->first ? 'active' : '' }}"
                                    data-gallery-image="{{ $galleryImage['url'] }}"
                                    aria-label="Show product image {{ $loop->iteration }}"
                                    aria-pressed="{{ $loop->first ? 'true' : 'false' }}"
                                >
                                    <img src="{{ $galleryImage['url'] }}" alt="" loading="lazy">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Product Info Column --}}
            <div class="col-lg-6">
                @if ($product->category)
                    <div class="product-card-category mb-2">
                        <a href="{{ route('store.home', ['category_id' => $product->category->id]) }}" class="text-primary text-decoration-none fw-bold small">
                            <i class="bi bi-tag-fill me-1"></i> {{ $product->category->name }}
                        </a>
                    </div>
                @endif

                <h1 class="fw-bold text-dark mb-2" style="font-size: clamp(1.8rem, 3vw, 2.5rem); letter-spacing: -0.5px;">
                    {{ $product->name }}
                </h1>

                {{-- Rating Quick Link --}}
                <div class="mb-3">
                    <a href="#reviews" class="d-inline-flex align-items-center gap-2 text-decoration-none">
                        <span class="star-rating">
                            @php $avgRating = $product->average_rating; @endphp
                            @for ($i = 1; $i <= 5; $i++)
                                <i class="bi bi-star-fill {{ $i <= round($avgRating) ? '' : 'star-muted' }}"></i>
                            @endfor
                        </span>
                        <span class="fw-bold text-dark small">{{ number_format($avgRating, 1) }}</span>
                        <span class="text-muted small">({{ $product->reviews_count }} {{ Str::plural('review', $product->reviews_count) }})</span>
                        <span class="text-primary small text-decoration-underline ms-1">Read reviews</span>
                    </a>
                </div>

                <div class="d-flex align-items-center gap-3 mb-3 text-muted small">
                    @if ($product->sku)
                        <span>SKU: <strong class="text-dark">{{ $product->sku }}</strong></span>
                        <span>•</span>
                    @endif
                    <span>
                        @if ($availableStock > 0)
                            <span class="text-success fw-bold" data-product-stock><i class="bi bi-check-circle-fill me-1"></i> In Stock ({{ $availableStock }} available)</span>
                        @else
                            <span class="text-danger fw-bold" data-product-stock><i class="bi bi-x-circle-fill me-1"></i> Currently Out of Stock</span>
                        @endif
                    </span>
                </div>

                {{-- Price Display --}}
                <div class="d-flex align-items-baseline gap-3 mb-4 pb-3 border-bottom">
                    <span class="fs-2 fw-bold text-dark" data-product-price>${{ number_format($product->price, 2) }}</span>
                    @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                        <span class="fs-5 text-muted text-decoration-line-through">
                            ${{ number_format($product->compare_at_price, 2) }}
                        </span>
                    @endif
                </div>

                {{-- Product Description --}}
                <div class="text-secondary mb-4 leading-relaxed">
                    {!! nl2br(e($product->description ?? 'Discover thoughtful craftsmanship and premium everyday quality designed to stand the test of time.')) !!}
                </div>

                {{-- Add to Cart & Wishlist Controls --}}
                @php
                    $inWishlist = auth()->check()
                        ? auth()->user()->wishlists->contains('product_id', $product->id)
                        : in_array($product->id, (array) session('wishlist', []));
                @endphp

                <div class="d-flex flex-column align-items-stretch gap-3 mb-4">
                    @if ($availableStock > 0)
                        <form id="productAddForm" method="POST" action="{{ route('store.cart.add', $product) }}" class="product-option-form" data-cart-add>
                            @csrf
                            @if ($activeVariants->isNotEmpty())
                                <div
                                    class="product-option-selectors"
                                    data-product-options
                                    data-product-id="{{ $product->id }}"
                                    data-base-price="{{ $product->price }}"
                                    data-variants="{{ $activeVariants->map(fn ($variant) => ['id' => $variant->id, 'size' => $variant->size, 'color' => $variant->color, 'price' => $variant->price, 'stock' => $variant->stock_quantity])->values()->toJson() }}"
                                >
                                    <div class="row g-3">
                                        @if ($variantSizes->isNotEmpty())
                                            <div class="col-sm-6">
                                                <label for="productSize" class="form-label small fw-semibold">Size</label>
                                                <select id="productSize" class="form-select" data-variant-option="size" required>
                                                    <option value="">Choose a size</option>
                                                    @foreach ($variantSizes as $size)
                                                        <option value="{{ $size }}">{{ $size }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                        @if ($variantColors->isNotEmpty())
                                            <div class="col-sm-6">
                                                <label for="productColor" class="form-label small fw-semibold">Color</label>
                                                <select id="productColor" class="form-select" data-variant-option="color" required>
                                                    <option value="">Choose a color</option>
                                                    @foreach ($variantColors as $color)
                                                        <option value="{{ $color }}">{{ $color }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="hidden" name="variant_id" value="" data-selected-variant required>
                                    <div class="small text-muted mt-2" data-variant-stock>Select your options to see availability.</div>
                                </div>
                            @endif
                        </form>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="quantity-picker shadow-xs">
                                <button type="button" class="quantity-btn" data-quantity-decrement aria-label="Decrease quantity">
                                    <i class="bi bi-dash"></i>
                                </button>
                                <input type="number" name="quantity" form="productAddForm" class="quantity-input" value="1" min="1" max="{{ $availableStock }}" readonly>
                                <button type="button" class="quantity-btn" data-quantity-increment aria-label="Increase quantity">
                                    <i class="bi bi-plus"></i>
                                </button>
                            </div>

                            <button type="submit" form="productAddForm" class="btn btn-store-primary btn-store-lg flex-grow-1" @if ($activeVariants->isNotEmpty()) disabled @endif>
                                <i class="bi bi-bag-plus me-2"></i> Add to Cart
                            </button>

                            <form method="POST" action="{{ route('store.wishlist.toggle', $product) }}">
                                @csrf
                                <button type="submit" class="btn btn-store-outline btn-store-lg" title="Save to wishlist">
                                    <i class="bi {{ $inWishlist ? 'bi-heart-fill text-danger' : 'bi-heart' }} me-2"></i>
                                    {{ $inWishlist ? 'Saved' : 'Wishlist' }}
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="alert alert-warning rounded-3 border-0 shadow-xs mb-0 flex-grow-1 py-2">
                            <i class="bi bi-bell me-2"></i> This item is currently out of stock.
                        </div>
                    @endif
                </div>

                {{-- Trust Badges --}}
                <div class="card bg-light border-0 rounded-3 p-3">
                    <div class="row g-2 text-center text-sm-start">
                        <div class="col-sm-4 d-flex align-items-center gap-2">
                            <i class="bi bi-truck fs-5 text-primary"></i>
                            <span class="small text-secondary">Free Shipping over $75</span>
                        </div>
                        <div class="col-sm-4 d-flex align-items-center gap-2">
                            <i class="bi bi-shield-check fs-5 text-success"></i>
                            <span class="small text-secondary">Authentic Quality</span>
                        </div>
                        <div class="col-sm-4 d-flex align-items-center gap-2">
                            <i class="bi bi-arrow-repeat fs-5 text-info"></i>
                            <span class="small text-secondary">30-Day Returns</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Product Reviews & Star Ratings Section --}}
    <section id="reviews" class="py-5 border-top">
        <div class="d-flex justify-content-between align-items-baseline mb-4">
            <div>
                <span class="text-primary fw-bold text-uppercase small tracking-wide">Community Feedback</span>
                <h3 class="fw-bold text-dark mb-0">Customer Reviews & Ratings</h3>
            </div>
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ $product->reviews_count }} Verified {{ Str::plural('Review', $product->reviews_count) }}
            </span>
        </div>

        <div class="row g-5">
            {{-- Left Column: Rating Breakdown Card --}}
            <div class="col-lg-4">
                <div class="card border rounded-4 p-4 bg-white shadow-xs">
                    <h5 class="fw-bold text-dark mb-3">Overall Rating</h5>

                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="display-4 fw-bold text-dark">{{ number_format($avgRating, 1) }}</div>
                        <div>
                            <div class="star-rating fs-5 mb-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <i class="bi bi-star-fill {{ $i <= round($avgRating) ? '' : 'star-muted' }}"></i>
                                @endfor
                            </div>
                            <div class="small text-muted">Based on {{ $product->reviews_count }} {{ Str::plural('rating', $product->reviews_count) }}</div>
                        </div>
                    </div>

                    {{-- Star Breakdown Percentages --}}
                    <div class="pt-2 border-top">
                        @for ($star = 5; $star >= 1; $star--)
                            @php
                                $count = $product->approvedReviews->where('rating', $star)->count();
                                $pct = $product->reviews_count > 0 ? round(($count / $product->reviews_count) * 100) : 0;
                            @endphp
                            <div class="d-flex align-items-center gap-2 py-1 small">
                                <span class="text-secondary text-nowrap" style="width: 50px;">{{ $star }} stars</span>
                                <div class="progress flex-grow-1" style="height: 6px;">
                                    <div class="progress-bar bg-warning" style="width: {{ $pct }}%"></div>
                                </div>
                                <span class="text-muted text-end" style="width: 36px;">{{ $count }}</span>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            {{-- Right Column: Write Review Form & Review List --}}
            <div class="col-lg-8">
                {{-- Submit Review Form Card --}}
                <div class="card border rounded-4 p-4 bg-light mb-4 shadow-xs">
                    <h5 class="fw-bold text-dark mb-2">Leave Your Feedback</h5>
                    <p class="text-muted small mb-3">Share your experience regarding sizing, fit, and material quality.</p>

                    @auth
                        <form method="POST" action="{{ route('store.reviews.store', $product->slug) }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small fw-semibold text-secondary d-block">Overall Star Rating *</label>
                                <div class="star-rating-selector">
                                    <input type="radio" id="star5" name="rating" value="5" {{ old('rating', '5') == '5' ? 'checked' : '' }} required>
                                    <label for="star5" title="5 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star4" name="rating" value="4" {{ old('rating') == '4' ? 'checked' : '' }}>
                                    <label for="star4" title="4 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star3" name="rating" value="3" {{ old('rating') == '3' ? 'checked' : '' }}>
                                    <label for="star3" title="3 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star2" name="rating" value="2" {{ old('rating') == '2' ? 'checked' : '' }}>
                                    <label for="star2" title="2 stars"><i class="bi bi-star-fill"></i></label>
                                    <input type="radio" id="star1" name="rating" value="1" {{ old('rating') == '1' ? 'checked' : '' }}>
                                    <label for="star1" title="1 star"><i class="bi bi-star-fill"></i></label>
                                </div>
                                @error('rating')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label small fw-semibold text-secondary">Review Headline (Optional)</label>
                                <input
                                    type="text"
                                    id="title"
                                    name="title"
                                    class="form-control form-control-sm @error('title') is-invalid @enderror"
                                    value="{{ old('title') }}"
                                    placeholder="e.g. Perfect fit, exceptionally soft material"
                                    maxlength="150"
                                >
                                @error('title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="comment" class="form-label small fw-semibold text-secondary">Written Review *</label>
                                <textarea
                                    id="comment"
                                    name="comment"
                                    rows="3"
                                    class="form-control form-control-sm @error('comment') is-invalid @enderror"
                                    placeholder="Tell other shoppers about comfort, color accuracy, and overall value..."
                                    required
                                >{{ old('comment') }}</textarea>
                                @error('comment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-store-primary btn-store-sm px-4 fw-bold">
                                <i class="bi bi-send me-1"></i> Submit Customer Review
                            </button>
                        </form>
                    @else
                        <div class="p-3 bg-white border rounded-3 text-center">
                            <p class="text-muted small mb-3">Please sign in to your customer account to submit a product review.</p>
                            <a href="{{ route('login') }}" class="btn btn-store-outline btn-store-sm">
                                <i class="bi bi-person me-1"></i> Sign In to Leave Review
                            </a>
                        </div>
                    @endauth
                </div>

                {{-- Customer Reviews List --}}
                <div class="reviews-list">
                    @forelse ($product->approvedReviews as $review)
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                <div>
                                    <div class="fw-bold text-dark">{{ $review->user->name ?? 'Customer' }}</div>
                                    <div class="d-flex align-items-center gap-2 mt-1">
                                        <div class="star-rating">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <i class="bi bi-star-fill {{ $i <= $review->rating ? '' : 'star-muted' }}" style="font-size: 12px;"></i>
                                            @endfor
                                        </div>
                                        @if ($review->is_verified_purchase)
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill small py-1" style="font-size: 11px;">
                                                <i class="bi bi-patch-check-fill me-1"></i> Verified Buyer
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                <span class="text-muted small">{{ $review->created_at->format('M d, Y') }}</span>
                            </div>

                            @if ($review->title)
                                <h6 class="fw-bold text-dark mt-2 mb-1">{{ $review->title }}</h6>
                            @endif

                            <p class="text-secondary small mb-0 mt-2 leading-relaxed">
                                {{ $review->comment }}
                            </p>
                        </div>
                    @empty
                        <div class="card border rounded-4 text-center py-4 bg-white shadow-xs">
                            <p class="text-muted small mb-0">No customer reviews yet. Be the first to share your thoughts!</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    {{-- Related Products --}}
    @php
        $relatedProducts = \App\Models\Product::with(['category', 'activeVariants'])->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->when($product->category_id, fn($q) => $q->where('category_id', $product->category_id))
            ->limit(4)
            ->get();
    @endphp

    @if ($relatedProducts->isNotEmpty())
        <div class="mt-5 pt-4 border-top">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="fw-bold text-dark mb-1">You May Also Like</h3>
                    <p class="text-muted small mb-0">Complementary picks curated for your style</p>
                </div>
                <a href="{{ route('store.home') }}#featured-products" class="btn btn-store-outline btn-store-sm">
                    View Catalog <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                @foreach ($relatedProducts as $related)
                    <div class="col">
                        <div class="product-card">
                            <a href="{{ route('store.products.show', $related->slug) }}" class="product-card-img-wrapper">
                                @if ($related->image)
                                    <img src="{{ Str::startsWith($related->image, ['http://', 'https://']) ? $related->image : asset('storage/' . $related->image) }}" alt="{{ $related->name }}" class="product-card-img">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <i class="bi bi-image fs-2"></i>
                                    </div>
                                @endif

                                <div class="product-card-badges">
                                    @if ($related->compare_at_price && $related->compare_at_price > $related->price)
                                        <span class="product-badge badge-sale">Sale</span>
                                    @endif
                                </div>
                            </a>

                            <div class="product-card-body">
                                <div class="product-card-category">
                                    {{ $related->category->name ?? 'Collection' }}
                                </div>
                                <h6 class="product-card-title">
                                    <a href="{{ route('store.products.show', $related->slug) }}">
                                        {{ $related->name }}
                                    </a>
                                </h6>
                                <div class="product-card-footer">
                                    <div class="product-price-box">
                                        <span class="product-price">${{ number_format($related->price, 2) }}</span>
                                        @if ($related->compare_at_price && $related->compare_at_price > $related->price)
                                            <span class="product-compare-price">${{ number_format($related->compare_at_price, 2) }}</span>
                                        @endif
                                    </div>
                                    @if ($related->activeVariants->isNotEmpty())
                                        <a href="{{ route('store.products.show', $related->slug) }}" class="btn-card-add" title="Choose product options">
                                            <i class="bi bi-sliders"></i>
                                        </a>
                                    @else
                                        <form method="POST" action="{{ route('store.cart.add', $related) }}" data-cart-add>
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
    @endif
</div>
@endsection

@push('scripts')
<script>
    document.querySelectorAll('[data-product-gallery]').forEach((gallery) => {
        const mainImage = gallery.closest('.position-relative').querySelector('[data-gallery-main]');

        gallery.addEventListener('click', (event) => {
            const button = event.target.closest('[data-gallery-image]');
            if (!button || !mainImage) return;

            mainImage.style.opacity = '0.35';
            window.setTimeout(() => {
                mainImage.src = button.dataset.galleryImage;
                mainImage.alt = button.querySelector('img')?.alt || mainImage.alt;
                mainImage.style.opacity = '1';
            }, 120);

            gallery.querySelectorAll('[data-gallery-image]').forEach((thumbnail) => {
                const isSelected = thumbnail === button;
                thumbnail.classList.toggle('active', isSelected);
                thumbnail.setAttribute('aria-pressed', String(isSelected));
            });
        });
    });

    document.querySelectorAll('[data-product-options]').forEach((options) => {
        const variants = JSON.parse(options.dataset.variants);
        const selectors = [...options.querySelectorAll('[data-variant-option]')];
        const variantInput = options.querySelector('[data-selected-variant]');
        const stockLabel = options.querySelector('[data-variant-stock]');
        const form = options.closest('form');
        const formSelector = `[form="${CSS.escape(form.id)}"]`;
        const addButton = document.querySelector(`${formSelector}[type="submit"]`);
        const quantity = document.querySelector(`${formSelector}[name="quantity"]`);
        const price = document.querySelector('[data-product-price]');

        const updateSelection = () => {
            const selected = Object.fromEntries(
                selectors.map((selector) => [selector.dataset.variantOption, selector.value])
            );
            const requiredOptionsSelected = selectors.every((selector) => selector.value !== '');

            selectors.forEach((selector) => {
                [...selector.options].forEach((option) => {
                    if (!option.value) return;
                    const hasStock = variants.some((variant) => {
                        const otherOptionMatches = selectors.every((otherSelector) => {
                            if (otherSelector === selector || !selected[otherSelector.dataset.variantOption]) return true;
                            const key = otherSelector.dataset.variantOption;
                            return (variant[key] || '') === selected[key];
                        });
                        return variant[selector.dataset.variantOption] === option.value
                            && otherOptionMatches
                            && variant.stock > 0;
                    });
                    option.disabled = !hasStock;
                });
            });

            const variant = requiredOptionsSelected
                ? variants.find((candidate) => selectors.every((selector) => (
                    (candidate[selector.dataset.variantOption] || '') === selected[selector.dataset.variantOption]
                )))
                : null;

            variantInput.value = variant?.id || '';
            const isUnavailable = !variant || variant.stock < 1;
            addButton.disabled = isUnavailable;
            form.dataset.variantUnavailable = String(isUnavailable);
            quantity.max = variant?.stock || 1;
            quantity.value = '1';

            if (variant) {
                stockLabel.textContent = variant.stock > 0
                    ? `In stock (${variant.stock} available)`
                    : 'Currently out of stock';
                stockLabel.classList.toggle('text-danger', variant.stock < 1);
                stockLabel.classList.toggle('text-success', variant.stock > 0);
                price.textContent = `$${Number(variant.price ?? options.dataset.basePrice).toFixed(2)}`;
            } else {
                stockLabel.textContent = 'Select your options to see availability.';
                stockLabel.classList.remove('text-danger', 'text-success');
                price.textContent = `$${Number(options.dataset.basePrice).toFixed(2)}`;
            }
        };

        selectors.forEach((selector) => selector.addEventListener('change', updateSelection));
        form.addEventListener('cart:updated', (event) => {
            const cartItem = event.detail.cart_items?.find((item) => (
                item.product_id === Number(options.dataset.productId)
                && item.variant_id === Number(variantInput.value)
            ));

            if (!cartItem) return;

            const selectedVariant = variants.find((variant) => variant.id === cartItem.variant_id);
            if (selectedVariant) selectedVariant.stock = Math.max(0, cartItem.stock - cartItem.quantity);
            updateSelection();
        });
    });

    document.querySelectorAll('[data-quantity-decrement], [data-quantity-increment]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement.querySelector('[name="quantity"]');
            const delta = button.matches('[data-quantity-increment]') ? 1 : -1;
            input.value = Math.max(1, Math.min(Number(input.max), Number(input.value) + delta));
        });
    });
</script>
@endpush
