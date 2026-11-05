@extends('layouts.admin')

@section('title', 'Products Management')
@section('page_title', 'Product Catalog')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Products</h3>
            <p class="text-muted small mb-0">Manage catalog merchandise, pricing, and stock levels</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ $products->total() }} Total Products
            </span>
            <a href="{{ route('admin.products.create') }}" class="btn btn-store-primary btn-store-sm">
                <i class="bi bi-plus-lg me-1"></i> Add New Product
            </a>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="fw-bold text-dark">All Merchandise</h5>
        <span class="text-muted small">Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th style="width: 70px;">Item</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>SKU</th>
                    <th>Price</th>
                    <th>Inventory</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td>
                            @if ($product->image)
                                <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded border" style="width: 48px; height: 48px; object-fit: cover;">
                            @else
                                <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                    <i class="bi bi-box"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $product->name }}</div>
                            <a href="{{ route('store.products.show', $product->slug) }}" target="_blank" class="small text-secondary text-decoration-none">
                                View in store <i class="bi bi-box-arrow-up-right" style="font-size: 10px;"></i>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">
                                {{ $product->category->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="text-muted small">
                            <code>{{ $product->sku }}</code>
                        </td>
                        <td>
                            <div class="fw-bold text-dark">${{ number_format($product->price, 2) }}</div>
                            @if ($product->compare_at_price && $product->compare_at_price > $product->price)
                                <div class="text-muted small text-decoration-line-through">
                                    ${{ number_format($product->compare_at_price, 2) }}
                                </div>
                            @endif
                        </td>
                        <td>
                            @if ($product->available_stock > 10)
                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                    {{ $product->available_stock }} in stock
                                </span>
                            @elseif ($product->available_stock > 0)
                                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                                    Low: {{ $product->available_stock }} left
                                </span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                    Out of stock
                                </span>
                            @endif
                        </td>
                        <td>
                            @if ($product->is_active)
                                <span class="status-pill status-completed">Active</span>
                            @else
                                <span class="status-pill status-cancelled">Draft</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-light border" title="Edit Product">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger border" title="Delete Product">
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-box fs-1 mb-2 d-block opacity-50"></i>
                            <h6 class="fw-bold text-dark">No products found</h6>
                            <p class="small text-muted mb-3">Get started by creating your first catalog item.</p>
                            <a href="{{ route('admin.products.create') }}" class="btn btn-store-primary btn-store-sm">
                                <i class="bi bi-plus-lg me-1"></i> Add Product
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($products->hasPages())
        <div class="p-3 border-top d-flex justify-content-center">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
