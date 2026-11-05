<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    // Show all products
    public function index()
    {
        $products = Product::with(['category', 'activeVariants'])
            ->latest()
            ->paginate(10);

        return view('admin.products.index', compact('products'));
    }

    // Show create product page
    public function create()
    {
        $categories = Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        return view('admin.products.create', compact('categories'));
    }

    // Save a new product
    public function store(Request $request)
    {
        // Validate product information
        $validated = $request->validate($this->productRules());

        // Get product variants
        $variants = $this->validatedVariants($validated['variants'] ?? []);

        // Check variant SKUs
        $this->ensureUniqueVariantSkus($variants, $validated['sku']);

        // Check duplicate size and color combinations
        $this->ensureUniqueVariantOptions($variants);

        // Prepare product information
        $productData = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'is_active' => $validated['is_active'],
        ];

        // Create a unique slug
        $productData['slug'] = $this->uniqueSlug($validated['name']);

        // Upload main product image
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $productData['image'] = $image->store('products', 'public');
        }

        // Save product and its variants
        DB::transaction(function () use ($productData, $variants, $request) {

            $product = Product::create($productData);

            // Save product variants
            $this->syncVariants($product, $variants);

            // Save additional product images
            $this->storeImages($product, $request);
        });

        return redirect()->route('admin.products.index')
            ->with('success', 'Product created successfully.');
    }

    // Show edit product page
    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)
            ->whereNotNull('parent_id')
            ->with('parent')
            ->orWhere('id', $product->category_id)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();

        // Get product images and variants
        $product->load(['images', 'variants']);

        return view('admin.products.edit', compact('product', 'categories'));
    }

    // Update an existing product
    public function update(Request $request, Product $product)
    {
        // Validate product information
        $validated = $request->validate($this->productRules($product));

        // Get product variants
        $variants = $this->validatedVariants($validated['variants'] ?? []);

        // Check that variants belong to this product
        $this->ensureVariantOwnership($product, $variants);

        // Check variant SKUs
        $this->ensureUniqueVariantSkus($variants, $validated['sku'], $product);

        // Check duplicate size and color combinations
        $this->ensureUniqueVariantOptions($variants);

        // Check selected images belong to this product
        $this->ensureImageOwnership($product, $validated['remove_images'] ?? []);

        // Prepare product information
        $productData = [
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'sku' => $validated['sku'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'compare_at_price' => $validated['compare_at_price'] ?? null,
            'stock_quantity' => $validated['stock_quantity'],
            'is_active' => $validated['is_active'],
        ];

        // Update product slug
        $productData['slug'] = $this->uniqueSlug($validated['name'], $product);

        // Keep old main image
        $oldImage = $product->image;

        // Upload new main image if selected
        if ($request->hasFile('image')) {
            $image = $request->file('image');

            $productData['image'] = $image->store('products', 'public');
        }

        // Update product, variants and images
        DB::transaction(function () use ($product, $productData, $variants, $validated, $request) {

            // Update product information
            $product->update($productData);

            // Update product variants
            $this->syncVariants($product, $variants);

            // Remove selected images
            $this->removeImages($product, $validated['remove_images'] ?? []);

            // Upload new additional images
            $this->storeImages($product, $request);
        });

        // Delete old main image after uploading a new one
        if (
            $request->hasFile('image') &&
            $oldImage &&
            ! Str::startsWith($oldImage, ['http://', 'https://'])
        ) {
            Storage::disk('public')->delete($oldImage);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product updated successfully.');
    }

    // Delete a product
    public function destroy(Product $product)
    {
        // Get product images before deleting
        $product->load('images');

        $imagePaths = $product->images->pluck('image_path')->all();

        $mainImage = $product->image;

        // Delete product from database
        $product->delete();

        // Delete additional images from storage
        Storage::disk('public')->delete($imagePaths);

        // Delete main image
        if (
            $mainImage &&
            ! Str::startsWith($mainImage, ['http://', 'https://'])
        ) {
            Storage::disk('public')->delete($mainImage);
        }

        return redirect()->route('admin.products.index')
            ->with('success', 'Product deleted successfully.');
    }

    // Product validation rules
    private function productRules($product = null)
    {
        if ($product) {
            $skuRule = Rule::unique('products', 'sku')->ignore($product->id);
        } else {
            $skuRule = 'unique:products,sku';
        }

        return [
            'category_id' => 'required|integer|exists:categories,id',
            'name' => 'required|string|max:200',
            'sku' => ['required', 'string', 'max:100', $skuRule],
            'description' => 'nullable|string|max:10000',
            'price' => 'required|numeric|min:0|max:9999999999.99',
            'compare_at_price' => 'nullable|numeric|gt:price|max:9999999999.99',
            'stock_quantity' => 'required|integer|min:0',
            'is_active' => 'required|boolean',

            // Main product image
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            // Additional product images
            'images' => 'nullable|array|max:10',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048',

            // Images selected for removal
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer|exists:product_images,id',

            // Product variants
            'variants' => 'nullable|array|max:100',
            'variants.*.id' => 'nullable|integer|exists:product_variants,id',
            'variants.*.size' => 'nullable|string|max:50',
            'variants.*.color' => 'nullable|string|max:50',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0|max:9999999999.99',
            'variants.*.stock_quantity' => 'required|integer|min:0',
        ];
    }

    // Prepare and check product variants
    private function validatedVariants($rows)
    {
        $variants = [];

        foreach ($rows as $index => $row) {

            $size = trim($row['size'] ?? '');
            $color = trim($row['color'] ?? '');
            $sku = trim($row['sku'] ?? '');

            // Check if the variant has any information
            $hasValue = $size != '' || $color != '' || $sku != '';

            if (! $hasValue && empty($row['id'])) {
                continue;
            }

            // Every variant must have a size or color
            if ($size == '' && $color == '') {
                throw ValidationException::withMessages([
                    "variants.$index.size" => 'Each variant must have a size or a color.',
                ]);
            }

            $variants[] = [
                'id' => empty($row['id']) ? null : (int) $row['id'],
                'size' => $size == '' ? null : $size,
                'color' => $color == '' ? null : $color,
                'sku' => $sku == '' ? null : $sku,
                'price' => ($row['price'] ?? '') == '' ? null : $row['price'],
                'stock_quantity' => (int) $row['stock_quantity'],
            ];
        }

        return $variants;
    }

    // Check if variants belong to the selected product
    private function ensureVariantOwnership(Product $product, $variants)
    {
        foreach ($variants as $index => $variant) {

            if ($variant['id'] !== null) {

                $variantExists = $product->variants()
                    ->where('id', $variant['id'])
                    ->exists();

                if (! $variantExists) {
                    throw ValidationException::withMessages([
                        "variants.$index.id" => 'The selected variant does not belong to this product.',
                    ]);
                }
            }
        }
    }

    // Check that all variant SKUs are unique
    private function ensureUniqueVariantSkus($variants, $productSku, $product = null)
    {
        $usedSkus = [];

        foreach ($variants as $index => $variant) {

            $sku = $variant['sku'];

            if ($sku === null) {
                continue;
            }

            $lowerSku = Str::lower($sku);

            // Variant SKU cannot be the same as product SKU
            if (Str::lower($productSku) == $lowerSku) {
                throw ValidationException::withMessages([
                    "variants.$index.sku" => 'Variant SKUs must be unique and different from the product SKU.',
                ]);
            }

            // Check duplicate SKUs in the submitted variants
            if (isset($usedSkus[$lowerSku])) {
                throw ValidationException::withMessages([
                    "variants.$index.sku" => 'Variant SKUs must be unique and different from the product SKU.',
                ]);
            }

            // Check if another variant already uses this SKU
            $variantQuery = ProductVariant::whereRaw('LOWER(sku) = ?', [$lowerSku]);

            if ($variant['id'] !== null) {
                $variantQuery->where('id', '!=', $variant['id']);
            }

            // Check if another product uses this SKU
            $productQuery = Product::whereRaw('LOWER(sku) = ?', [$lowerSku]);

            if ($product) {
                $productQuery->where('id', '!=', $product->id);
            }

            if ($variantQuery->exists() || $productQuery->exists()) {
                throw ValidationException::withMessages([
                    "variants.$index.sku" => 'This variant SKU is already in use.',
                ]);
            }

            $usedSkus[$lowerSku] = true;
        }
    }

    // Check for duplicate size and color combinations
    private function ensureUniqueVariantOptions($variants)
    {
        $usedOptions = [];

        foreach ($variants as $index => $variant) {

            $size = Str::lower($variant['size'] ?? '');
            $color = Str::lower($variant['color'] ?? '');

            $option = $size.'|'.$color;

            if (isset($usedOptions[$option])) {
                throw ValidationException::withMessages([
                    "variants.$index.size" => 'Each size and color combination must be unique.',
                ]);
            }

            $usedOptions[$option] = true;
        }
    }

    // Save or update product variants
    private function syncVariants(Product $product, $variants)
    {
        $savedIds = [];

        foreach ($variants as $variant) {

            $variantId = $variant['id'];

            // Remove ID before saving variant information
            unset($variant['id']);

            if ($variantId !== null) {

                // Update existing variant
                $product->variants()
                    ->where('id', $variantId)
                    ->update($variant);

                $savedIds[] = $variantId;

            } else {

                // Create new variant
                $newVariant = $product->variants()->create($variant);

                $savedIds[] = $newVariant->id;
            }
        }

        // Delete variants that were removed from the form
        $variantsToDelete = $product->variants();

        if (count($savedIds) > 0) {
            $variantsToDelete->whereNotIn('id', $savedIds);
        }

        $variantsToDelete->delete();
    }

    // Upload additional product images
    private function storeImages(Product $product, Request $request)
    {
        $nextOrder = (int) $product->images()->max('sort_order') + 1;

        $images = $request->file('images', []);

        foreach ($images as $index => $image) {

            $imagePath = $image->store('products', 'public');

            $product->images()->create([
                'image_path' => $imagePath,
                'sort_order' => $nextOrder + $index,
            ]);
        }
    }

    // Check if selected images belong to this product
    private function ensureImageOwnership(Product $product, $imageIds)
    {
        foreach ($imageIds as $imageId) {

            $imageExists = $product->images()
                ->where('id', $imageId)
                ->exists();

            if (! $imageExists) {
                throw ValidationException::withMessages([
                    'remove_images' => 'A selected image does not belong to this product.',
                ]);
            }
        }
    }

    // Remove selected product images
    private function removeImages(Product $product, $imageIds)
    {
        if (count($imageIds) == 0) {
            return;
        }

        $images = $product->images()
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {

            // Delete image file
            Storage::disk('public')->delete($image->image_path);

            // Delete image record
            $image->delete();
        }
    }

    // Generate a unique product slug
    private function uniqueSlug($name, $product = null)
    {
        $slug = Str::slug($name);

        if ($slug == '') {
            $slug = 'product';
        }

        $originalSlug = $slug;
        $counter = 1;

        // Check if slug already exists
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
