<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_a_product_with_variant_stock_and_gallery_images(): void
    {
        Storage::fake('public');
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::create([
            'name' => 'Outerwear',
            'slug' => 'outerwear',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Reversible Jacket',
            'sku' => 'JACKET-BASE',
            'description' => 'A lightweight reversible jacket.',
            'price' => 140,
            'stock_quantity' => 0,
            'is_active' => 1,
            'variants' => [
                [
                    'size' => 'M',
                    'color' => 'Navy',
                    'sku' => 'JACKET-M-NAVY',
                    'price' => 145,
                    'stock_quantity' => 7,
                ],
            ],
            'images' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('back.jpg'),
            ],
        ]);

        $product = Product::where('sku', 'JACKET-BASE')->firstOrFail();
        $response->assertRedirect(route('admin.products.index'));
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy',
            'sku' => 'JACKET-M-NAVY',
            'price' => '145.00',
            'stock_quantity' => 7,
        ]);
        $this->assertSame(2, ProductImage::where('product_id', $product->id)->count());

        foreach (ProductImage::where('product_id', $product->id)->get() as $image) {
            Storage::disk('public')->assertExists($image->image_path);
        }
    }

    public function test_admin_must_use_unique_size_and_color_combinations(): void
    {
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $category = Category::create([
            'name' => 'Basics',
            'slug' => 'basics',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'category_id' => $category->id,
                'name' => 'Cotton Tee',
                'sku' => 'TEE-BASE',
                'price' => 25,
                'stock_quantity' => 0,
                'is_active' => 1,
                'variants' => [
                    ['size' => 'S', 'color' => 'Black', 'stock_quantity' => 2],
                    ['size' => 'S', 'color' => 'Black', 'stock_quantity' => 3],
                ],
            ]);

        $response->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('variants.1.size');
        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_detail_lets_shoppers_select_variants_and_gallery_images(): void
    {
        $category = Category::create([
            'name' => 'Tops',
            'slug' => 'tops',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Merino Polo',
            'slug' => 'merino-polo',
            'sku' => 'POLO-BASE',
            'price' => 80,
            'stock_quantity' => 0,
            'image' => 'products/polo-front.jpg',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Charcoal',
            'stock_quantity' => 3,
            'is_active' => true,
        ]);
        ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/polo-back.jpg',
            'sort_order' => 1,
        ]);

        $response = $this->get(route('store.products.show', $product->slug));

        $response->assertOk()
            ->assertSee('Choose a size')
            ->assertSee('Choose a color')
            ->assertSee('Show product image 2');
    }
}
