@forelse ($items as $item)
    @php
        $product = $item['product'];
        $image = $product->image;
    @endphp
    <div class="cart-drawer-item">
        <a href="{{ route('store.products.show', $product->slug) }}" class="cart-drawer-item-image">
            @if ($image)
                <img
                    src="{{ Str::startsWith($image, ['http://', 'https://']) ? $image : asset('storage/' . $image) }}"
                    alt="{{ $product->name }}"
                >
            @else
                <i class="bi bi-image text-muted"></i>
            @endif
        </a>
        <div class="flex-grow-1 overflow-hidden">
            <a href="{{ route('store.products.show', $product->slug) }}" class="fw-semibold text-dark text-decoration-none">
                {{ $product->name }}
            </a>
            @if ($item['variant_name'])
                <div class="small text-muted">{{ $item['variant_name'] }}</div>
            @endif
            <div class="small text-muted">Qty {{ $item['quantity'] }} × ${{ number_format($item['unit_price'], 2) }}</div>
            <form method="POST" action="{{ route('store.cart.remove', $product) }}" class="mt-1" data-cart-remove>
                @csrf
                @method('DELETE')
                @if ($item['variant_id'])
                    <input type="hidden" name="variant_id" value="{{ $item['variant_id'] }}">
                @endif
                <button type="submit" class="btn btn-link text-danger p-0 border-0 small text-decoration-none">
                    <i class="bi bi-trash3 me-1"></i> Remove
                </button>
            </form>
        </div>
        <div class="fw-bold text-dark small">${{ number_format($item['subtotal'], 2) }}</div>
    </div>
@empty
    <div class="cart-drawer-empty">
        <i class="bi bi-bag-x fs-2 mb-2"></i>
        <p class="mb-0">Your cart is currently empty.</p>
    </div>
@endforelse
