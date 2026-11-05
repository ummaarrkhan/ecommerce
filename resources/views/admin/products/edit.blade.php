@extends('layouts.admin')

@section('title', 'Edit ' . $product->name)
@section('page_title', 'Edit Product')

@section('content')
<div class="mb-4">
    <div class="d-flex align-items-center justify-content-between">
        <div>
            <a href="{{ route('admin.products.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Products
            </a>
            <h3 class="fw-bold text-dark mt-2 mb-0">Edit Product: {{ $product->name }}</h3>
        </div>
        <a href="{{ route('store.products.show', $product->slug) }}" target="_blank" class="btn btn-store-outline btn-store-sm">
            <i class="bi bi-eye me-1"></i> Preview in Store
        </a>
    </div>
</div>

<form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Left Form Column --}}
        <div class="col-lg-8">
            <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
                <h5 class="fw-bold text-dark mb-3">General Information</h5>

                <div class="mb-3">
                    <label for="name" class="form-label small fw-semibold text-secondary">Product Name *</label>
                    <input
                        type="text"
                        name="name"
                        id="name"
                        class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name', $product->name) }}"
                        required
                    >
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label small fw-semibold text-secondary">Description</label>
                    <textarea
                        name="description"
                        id="description"
                        rows="5"
                        class="form-control @error('description') is-invalid @enderror"
                    >{{ old('description', $product->description) }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
                <h5 class="fw-bold text-dark mb-3">Pricing & Inventory</h5>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="price" class="form-label small fw-semibold text-secondary">Price ($) *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input
                                type="number"
                                step="0.01"
                                name="price"
                                id="price"
                                class="form-control @error('price') is-invalid @enderror"
                                value="{{ old('price', $product->price) }}"
                                required
                            >
                        </div>
                        @error('price')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="compare_at_price" class="form-label small fw-semibold text-secondary">Compare at Price ($)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">$</span>
                            <input
                                type="number"
                                step="0.01"
                                name="compare_at_price"
                                id="compare_at_price"
                                class="form-control @error('compare_at_price') is-invalid @enderror"
                                value="{{ old('compare_at_price', $product->compare_at_price) }}"
                            >
                        </div>
                        <span class="form-text small">Original price for showing discount tag.</span>
                        @error('compare_at_price')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="sku" class="form-label small fw-semibold text-secondary">SKU *</label>
                        <input
                            type="text"
                            name="sku"
                            id="sku"
                            class="form-control @error('sku') is-invalid @enderror"
                            value="{{ old('sku', $product->sku) }}"
                            required
                        >
                        @error('sku')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="stock_quantity" class="form-label small fw-semibold text-secondary">Simple Product Stock *</label>
                        <input
                            type="number"
                            name="stock_quantity"
                            id="stock_quantity"
                            class="form-control @error('stock_quantity') is-invalid @enderror"
                            value="{{ old('stock_quantity', $product->stock_quantity) }}"
                            min="0"
                            required
                        >
                        @error('stock_quantity')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
                    <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Size & Color Variants</h5>
                            <p class="form-text small mb-0">Each row tracks stock for one size/color combination.</p>
                        </div>
                        <button type="button" class="btn btn-store-outline btn-store-sm" data-add-variant>
                            <i class="bi bi-plus-lg me-1"></i> Add Variant
                        </button>
                    </div>

                    @php
                        $variantRows = old('variants', $product->variants->map(fn ($variant) => [
                            'id' => $variant->id,
                            'size' => $variant->size,
                            'color' => $variant->color,
                            'sku' => $variant->sku,
                            'price' => $variant->price,
                            'stock_quantity' => $variant->stock_quantity,
                        ])->all());
                    @endphp

                    <div class="d-grid gap-3" data-variant-rows>
                        @foreach ($variantRows as $index => $variant)
                            <div class="row g-2 align-items-end border rounded-3 p-3 mx-0" data-variant-row>
                                @if (! empty($variant['id']))
                                    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] }}">
                                @endif
                                <div class="col-6 col-md-4 col-xl-2">
                                    <label class="form-label small" for="variant-size-{{ $index }}">Size</label>
                                    <input id="variant-size-{{ $index }}" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" class="form-control" placeholder="M">
                                </div>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <label class="form-label small" for="variant-color-{{ $index }}">Color</label>
                                    <input id="variant-color-{{ $index }}" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" class="form-control" placeholder="Navy">
                                </div>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <label class="form-label small" for="variant-sku-{{ $index }}">SKU</label>
                                    <input id="variant-sku-{{ $index }}" name="variants[{{ $index }}][sku]" value="{{ $variant['sku'] ?? '' }}" class="form-control" placeholder="Optional">
                                </div>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <label class="form-label small" for="variant-price-{{ $index }}">Price</label>
                                    <input id="variant-price-{{ $index }}" type="number" step="0.01" min="0" name="variants[{{ $index }}][price]" value="{{ $variant['price'] ?? '' }}" class="form-control" placeholder="Base price">
                                </div>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <label class="form-label small" for="variant-stock-{{ $index }}">Stock</label>
                                    <input id="variant-stock-{{ $index }}" type="number" min="0" name="variants[{{ $index }}][stock_quantity]" value="{{ $variant['stock_quantity'] ?? 0 }}" class="form-control" required>
                                </div>
                                <div class="col-6 col-md-4 col-xl-2">
                                    <button type="button" class="btn btn-outline-danger w-100" data-remove-variant aria-label="Remove variant">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <template data-variant-row-template>
                        <div class="row g-2 align-items-end border rounded-3 p-3 mx-0" data-variant-row>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small" for="variant-size-__INDEX__">Size</label>
                                <input id="variant-size-__INDEX__" name="variants[__INDEX__][size]" class="form-control" placeholder="M">
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small" for="variant-color-__INDEX__">Color</label>
                                <input id="variant-color-__INDEX__" name="variants[__INDEX__][color]" class="form-control" placeholder="Navy">
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small" for="variant-sku-__INDEX__">SKU</label>
                                <input id="variant-sku-__INDEX__" name="variants[__INDEX__][sku]" class="form-control" placeholder="Optional">
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small" for="variant-price-__INDEX__">Price</label>
                                <input id="variant-price-__INDEX__" type="number" step="0.01" min="0" name="variants[__INDEX__][price]" class="form-control" placeholder="Base price">
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <label class="form-label small" for="variant-stock-__INDEX__">Stock</label>
                                <input id="variant-stock-__INDEX__" type="number" min="0" name="variants[__INDEX__][stock_quantity]" value="0" class="form-control" required>
                            </div>
                            <div class="col-6 col-md-4 col-xl-2">
                                <button type="button" class="btn btn-outline-danger w-100" data-remove-variant aria-label="Remove variant">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Right Column (Organization & Media) --}}
        <div class="col-lg-4">
            <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
                <h5 class="fw-bold text-dark mb-3">Organization</h5>

                <div class="mb-3">
                    <label for="category_id" class="form-label small fw-semibold text-secondary">Category *</label>
                    <select name="category_id" id="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                        <option value="">Select a category...</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->parent->name ?? 'Category' }} &rsaquo; {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary d-block">Visibility</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active1" value="1" {{ old('is_active', $product->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="active1">Published</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="is_active" id="active0" value="0" {{ old('is_active', $product->is_active ? '1' : '0') == '0' ? 'checked' : '' }}>
                        <label class="form-check-label small" for="active0">Draft</label>
                    </div>
                </div>
            </div>

            <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
                <h5 class="fw-bold text-dark mb-3">Product Media</h5>

                @if ($product->image)
                    <div class="mb-3 text-center">
                        <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded border p-1" style="max-height: 160px; object-fit: cover;">
                        <div class="small text-muted mt-1">Current Primary Image</div>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="image" class="form-label small fw-semibold text-secondary">Replace Primary Image</label>
                    <input
                        type="file"
                        name="image"
                        id="image"
                        class="form-control @error('image') is-invalid @enderror"
                        accept="image/png,image/jpeg,image/webp"
                    >
                    <span class="form-text small">Accepted: JPG, PNG, WEBP (Max: 2MB).</span>
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                @if ($product->images->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Current Gallery</label>
                        <div class="d-grid gap-2">
                            @foreach ($product->images as $galleryImage)
                                <label class="d-flex align-items-center gap-2 border rounded-3 p-2">
                                    <img src="{{ $galleryImage->url }}" alt="Gallery image {{ $loop->iteration }}" class="rounded" style="width: 56px; height: 56px; object-fit: cover;">
                                    <span class="small flex-grow-1">Photo {{ $loop->iteration }}</span>
                                    <input type="checkbox" name="remove_images[]" value="{{ $galleryImage->id }}" aria-label="Remove photo {{ $loop->iteration }}">
                                    <span class="small text-danger">Remove</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mb-3">
                    <label for="images" class="form-label small fw-semibold text-secondary">Add Gallery Images</label>
                    <input
                        type="file"
                        name="images[]"
                        id="images"
                        class="form-control @error('images') is-invalid @enderror"
                        accept="image/png,image/jpeg,image/webp"
                        multiple
                    >
                    <span class="form-text small">Upload up to 10 additional JPG, PNG, or WEBP photos (2MB each).</span>
                    @error('images')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    @error('images.*')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                    @error('remove_images')
                        <div class="text-danger small">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="card border rounded-3 p-3 bg-white shadow-xs">
                <button type="submit" class="btn btn-store-primary w-100 py-2 fw-bold mb-2">
                    <i class="bi bi-check2 me-1"></i> Update Product
                </button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-store-outline w-100 py-2">
                    Cancel
                </a>
            </div>
        </div>
    </div>
</form>

<script>
    (() => {
        const rows = document.querySelector('[data-variant-rows]');
        const template = document.querySelector('[data-variant-row-template]');
        let nextIndex = [...(rows?.querySelectorAll('[name^="variants["]') ?? [])]
            .reduce((next, input) => Math.max(next, Number(input.name.match(/^variants\[(\d+)\]/)?.[1] ?? -1) + 1), 0);

        document.querySelector('[data-add-variant]')?.addEventListener('click', () => {
            rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', nextIndex++));
        });

        rows?.addEventListener('click', (event) => {
            const button = event.target.closest('[data-remove-variant]');
            if (button) {
                button.closest('[data-variant-row]').remove();
            }
        });
    })();
</script>
@endsection
