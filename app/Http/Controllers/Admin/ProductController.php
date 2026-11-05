<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(): View
    {
        $products = Product::with(['category', 'activeVariants'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->productRules());
        $variants = $this->validatedVariants($validated['variants'] ?? []);
        $this->ensureUniqueVariantSkus($variants, $validated['sku']);
        $this->ensureUniqueVariantOptions($variants);

        $productData = Arr::only($validated, [
            'category_id',
            'name',
            'sku',
            'description',
            'price',
            'compare_at_price',
            'stock_quantity',
            'is_active',
        ]);
        $productData['slug'] = $this->uniqueSlug($validated['name']);

        if ($request->hasFile('image')) {
            $productData['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($productData, $variants, $request): void {
            $product = Product::create($productData);
            $this->syncVariants($product, $variants);
            $this->storeImages($product, $request);
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    public function edit(Product $product): View
    {
        $categories = Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orWhere('id', $product->category_id)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate($this->productRules($product));
        $variants = $this->validatedVariants($validated['variants'] ?? []);
        $this->ensureVariantOwnership($product, $variants);
        $this->ensureUniqueVariantSkus($variants, $validated['sku'], $product);
        $this->ensureUniqueVariantOptions($variants);
        $this->ensureImageOwnership($product, $validated['remove_images'] ?? []);

        $productData = Arr::only($validated, [
            'category_id',
            'name',
            'sku',
            'description',
            'price',
            'compare_at_price',
            'stock_quantity',
            'is_active',
        ]);
        $productData['slug'] = $this->uniqueSlug($validated['name'], $product);
        $oldImage = $product->image;

        if ($request->hasFile('image')) {
            $productData['image'] = $request->file('image')->store('products', 'public');
        }

        DB::transaction(function () use ($product, $productData, $variants, $validated, $request): void {
            $product->update($productData);
            $this->syncVariants($product, $variants);
            $this->removeImages($product, $validated['remove_images'] ?? []);
            $this->storeImages($product, $request);
        });

        if ($request->hasFile('image') && $oldImage && ! Str::startsWith($oldImage, ['http://', 'https://'])) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->load('images');
        $imagePaths = $product->images->pluck('image_path')->all();
        $primaryImage = $product->image;

        $product->delete();

        Storage::disk('public')->delete($imagePaths);

        if ($primaryImage && ! Str::startsWith($primaryImage, ['http://', 'https://'])) {
            Storage::disk('public')->delete($primaryImage);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function productRules(?Product $product = null): array
    {
        return [
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:200'],
            'sku' => [
                'required',
                'string',
                'max:100',
                $product
                    ? Rule::unique('products', 'sku')->ignore($product->id)
                    : 'unique:products,sku',
            ],
            'description' => ['nullable', 'string', 'max:10000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'compare_at_price' => ['nullable', 'numeric', 'gt:price', 'max:9999999999.99'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer', 'exists:product_images,id'],
            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.size' => ['nullable', 'string', 'max:50'],
            'variants.*.color' => ['nullable', 'string', 'max:50'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            'variants.*.stock_quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function validatedVariants(array $rows): array
    {
        $variants = [];

        foreach ($rows as $index => $row) {
            $size = trim((string) ($row['size'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            $sku = trim((string) ($row['sku'] ?? ''));
            $hasAnyValue = $size !== '' || $color !== '' || $sku !== '';

            if (! $hasAnyValue && empty($row['id'])) {
                continue;
            }

            if ($size === '' && $color === '') {
                throw ValidationException::withMessages([
                    "variants.{$index}.size" => 'Each variant must have a size or a color.',
                ]);
            }

            $variants[] = [
                'id' => empty($row['id']) ? null : (int) $row['id'],
                'size' => $size === '' ? null : $size,
                'color' => $color === '' ? null : $color,
                'sku' => $sku === '' ? null : $sku,
                'price' => ($row['price'] ?? '') === '' ? null : $row['price'],
                'stock_quantity' => (int) $row['stock_quantity'],
            ];
        }

        return $variants;
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function ensureVariantOwnership(Product $product, array $variants): void
    {
        foreach ($variants as $index => $variant) {
            if (
                $variant['id'] !== null
                && ! $product->variants()->whereKey($variant['id'])->exists()
            ) {
                throw ValidationException::withMessages([
                    "variants.{$index}.id" => 'The selected variant does not belong to this product.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function ensureUniqueVariantSkus(
        array $variants,
        string $productSku,
        ?Product $product = null
    ): void {
        $seenSkus = [];

        foreach ($variants as $index => $variant) {
            $sku = $variant['sku'];

            if ($sku === null) {
                continue;
            }

            $normalizedSku = Str::lower($sku);

            if (
                $normalizedSku === Str::lower($productSku)
                || isset($seenSkus[$normalizedSku])
            ) {
                throw ValidationException::withMessages([
                    "variants.{$index}.sku" => 'Variant SKUs must be unique and different from the product SKU.',
                ]);
            }

            $variantQuery = ProductVariant::whereRaw('LOWER(sku) = ?', [$normalizedSku]);

            if ($variant['id'] !== null) {
                $variantQuery->where('id', '!=', $variant['id']);
            }

            $existingProductSku = Product::whereRaw('LOWER(sku) = ?', [$normalizedSku]);

            if ($product) {
                $existingProductSku->where('id', '!=', $product->id);
            }

            $existingVariant = $variantQuery->exists() || $existingProductSku->exists();

            if ($existingVariant) {
                throw ValidationException::withMessages([
                    "variants.{$index}.sku" => 'This variant SKU is already in use.',
                ]);
            }

            $seenSkus[$normalizedSku] = true;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function ensureUniqueVariantOptions(array $variants): void
    {
        $seenOptions = [];

        foreach ($variants as $index => $variant) {
            $optionKey = Str::lower((string) $variant['size']).'|'.Str::lower((string) $variant['color']);

            if (isset($seenOptions[$optionKey])) {
                throw ValidationException::withMessages([
                    "variants.{$index}.size" => 'Each size and color combination must be unique.',
                ]);
            }

            $seenOptions[$optionKey] = true;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $variants
     */
    private function syncVariants(Product $product, array $variants): void
    {
        $variantIds = [];

        foreach ($variants as $variant) {
            $attributes = Arr::except($variant, ['id']);

            if ($variant['id'] !== null) {
                $product->variants()->whereKey($variant['id'])->update($attributes);
                $variantIds[] = $variant['id'];
            } else {
                $createdVariant = $product->variants()->create($attributes);
                $variantIds[] = $createdVariant->id;
            }
        }

        $variantsToDelete = $product->variants();

        if ($variantIds !== []) {
            $variantsToDelete->whereNotIn('id', $variantIds);
        }

        $variantsToDelete->delete();
    }

    private function storeImages(Product $product, Request $request): void
    {
        $nextSortOrder = (int) $product->images()->max('sort_order') + 1;

        foreach ($request->file('images', []) as $index => $image) {
            $product->images()->create([
                'image_path' => $image->store('products', 'public'),
                'sort_order' => $nextSortOrder + $index,
            ]);
        }
    }

    /**
     * @param  array<int, int|string>  $imageIds
     */
    private function ensureImageOwnership(Product $product, array $imageIds): void
    {
        foreach ($imageIds as $imageId) {
            if (! $product->images()->whereKey($imageId)->exists()) {
                throw ValidationException::withMessages([
                    'remove_images' => 'A selected image does not belong to this product.',
                ]);
            }
        }
    }

    /**
     * @param  array<int, int|string>  $imageIds
     */
    private function removeImages(Product $product, array $imageIds): void
    {
        if ($imageIds === []) {
            return;
        }

        $images = $product->images()->whereIn('id', $imageIds)->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image_path);
            $image->delete();
        }
    }

    private function uniqueSlug(string $name, ?Product $product = null): string
    {
        $slug = Str::slug($name) ?: 'product';
        $originalSlug = $slug;
        $counter = 1;

        $query = Product::where('slug', $slug);

        if ($product) {
            $query->where('id', '!=', $product->id);
        }

        while ($query->exists()) {
            $slug = $originalSlug.'-'.$counter;
            $counter++;
            $query = Product::where('slug', $slug);

            if ($product) {
                $query->where('id', '!=', $product->id);
            }
        }

        return $slug;
    }
}
