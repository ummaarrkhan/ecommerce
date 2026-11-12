<?php

namespace Tests\Feature\Store;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopper_can_add_a_selected_variant_to_the_ajax_cart(): void
    {
        $category = Category::create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt',
            'sku' => 'SHIRT-BASE',
            'price' => 80,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy',
            'sku' => 'SHIRT-M-NAVY',
            'price' => 85,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('store.cart.add', $product), [
            'quantity' => 2,
            'variant_id' => $variant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('subtotal', 170);
        $this->assertSame([
            $product->id.':'.$variant->id => [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ],
        ], session('cart'));
    }

    public function test_shopper_can_remove_one_variant_from_the_ajax_cart(): void
    {
        $category = Category::create([
            'name' => 'Apparel',
            'slug' => 'apparel-remove',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Linen Shirt',
            'slug' => 'linen-shirt-remove',
            'sku' => 'SHIRT-REMOVE',
            'price' => 80,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variantToRemove = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Navy',
            'price' => 85,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);
        $variantToKeep = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Navy',
            'price' => 85,
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $response = $this->withSession([
            'cart' => [
                $product->id.':'.$variantToRemove->id => [
                    'product_id' => $product->id,
                    'variant_id' => $variantToRemove->id,
                    'quantity' => 2,
                ],
                $product->id.':'.$variantToKeep->id => [
                    'product_id' => $product->id,
                    'variant_id' => $variantToKeep->id,
                    'quantity' => 1,
                ],
            ],
        ])->deleteJson(route('store.cart.remove', $product), [
            'variant_id' => $variantToRemove->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('subtotal', 85)
            ->assertJsonPath('message', 'Product removed from your cart.');
        $this->assertSame([
            $product->id.':'.$variantToKeep->id => [
                'product_id' => $product->id,
                'variant_id' => $variantToKeep->id,
                'quantity' => 1,
            ],
        ], session('cart'));
    }

    public function test_shopper_cannot_add_a_variant_belonging_to_another_product(): void
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Canvas Tote',
            'slug' => 'canvas-tote',
            'sku' => 'TOTE-BASE',
            'price' => 30,
            'stock_quantity' => 2,
            'is_active' => true,
        ]);
        $otherProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Tote',
            'slug' => 'leather-tote',
            'sku' => 'TOTE-LEATHER',
            'price' => 90,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $otherProduct->id,
            'size' => 'Large',
            'color' => 'Tan',
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('store.cart.add', $product), [
            'quantity' => 1,
            'variant_id' => $variant->id,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
        $this->assertSame([], session('cart', []));
    }

    public function test_shopper_must_select_a_variant_before_adding_a_product(): void
    {
        $category = Category::create([
            'name' => 'Trousers',
            'slug' => 'trousers',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Relaxed Chino',
            'slug' => 'relaxed-chino',
            'sku' => 'CHINO-BASE',
            'price' => 60,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'size' => '32',
            'color' => 'Oatmeal',
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('store.cart.add', $product), [
            'quantity' => 1,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('variant_id');
        $this->assertSame([], session('cart', []));
    }

    public function test_checkout_records_the_variant_and_decrements_its_stock(): void
    {
        $category = Category::create([
            'name' => 'Footwear',
            'slug' => 'footwear',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Sneaker',
            'slug' => 'leather-sneaker',
            'sku' => 'SNEAKER-BASE',
            'price' => 110,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => '42',
            'color' => 'Oatmeal',
            'sku' => 'SNEAKER-42-OAT',
            'price' => 125,
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'Sam Shopper', 'email' => 'sam@example.com']);

        $response = $this->actingAs($user)->withSession([
            'cart' => [
                $product->id.':'.$variant->id => [
                    'product_id' => $product->id,
                    'variant_id' => $variant->id,
                    'quantity' => 2,
                ],
            ],
        ])->post(route('store.checkout.store'), [
            'customer_name' => 'Sam Shopper',
            'customer_email' => 'sam@example.com',
            'customer_phone' => '555-0101',
            'shipping_address' => '1 Market Street',
            'shipping_city' => 'Portland',
            'shipping_postcode' => '97201',
            'shipping_country' => 'United States',
        ]);

        $order = Order::with('items')->firstOrFail();
        $response->assertRedirect(route('store.checkout.success', $order->order_number));
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Leather Sneaker',
            'variant_name' => 'Size: 42, Color: Oatmeal',
            'sku' => 'SNEAKER-42-OAT',
            'price' => '125.00',
            'quantity' => 2,
            'subtotal' => '250.00',
        ]);
        $this->assertSame(1, $variant->fresh()->stock_quantity);
        $this->assertSame([], session('cart', []));

        $receipt = $this->get(route('store.checkout.success', $order->order_number));
        $receipt->assertOk()
            ->assertSee('Size: 42, Color: Oatmeal');
    }

    public function test_checkout_preserves_simple_products_and_legacy_cart_sessions(): void
    {
        $category = Category::create([
            'name' => 'Home',
            'slug' => 'home',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cotton Throw',
            'slug' => 'cotton-throw',
            'sku' => 'THROW-BASE',
            'price' => 40,
            'stock_quantity' => 3,
            'is_active' => true,
        ]);

        $user = User::factory()->create(['name' => 'Sam Shopper', 'email' => 'sam@example.com']);

        $response = $this->actingAs($user)->withSession(['cart' => [$product->id => 1]])
            ->post(route('store.checkout.store'), [
                'customer_name' => 'Sam Shopper',
                'customer_email' => 'sam@example.com',
                'customer_phone' => '555-0101',
                'shipping_address' => '1 Market Street',
                'shipping_city' => 'Portland',
                'shipping_postcode' => '97201',
                'shipping_country' => 'United States',
            ]);

        $order = Order::with('items')->firstOrFail();
        $response->assertRedirect(route('store.checkout.success', $order->order_number));
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => null,
            'variant_name' => null,
            'sku' => 'THROW-BASE',
            'price' => '40.00',
            'quantity' => 1,
        ]);
        $this->assertSame(2, $product->fresh()->stock_quantity);
    }

    public function test_checkout_rejects_insufficient_variant_stock_without_creating_an_order(): void
    {
        $category = Category::create([
            'name' => 'Knitwear',
            'slug' => 'knitwear',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Wool Sweater',
            'slug' => 'wool-sweater',
            'sku' => 'SWEATER-BASE',
            'price' => 95,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'L',
            'color' => 'Charcoal',
            'stock_quantity' => 1,
            'is_active' => true,
        ]);
        $cart = [
            $product->id.':'.$variant->id => [
                'product_id' => $product->id,
                'variant_id' => $variant->id,
                'quantity' => 2,
            ],
        ];

        $user = User::factory()->create(['name' => 'Sam Shopper', 'email' => 'sam@example.com']);

        $response = $this->actingAs($user)->withSession(['cart' => $cart])
            ->post(route('store.checkout.store'), [
                'customer_name' => 'Sam Shopper',
                'customer_email' => 'sam@example.com',
                'customer_phone' => '555-0101',
                'shipping_address' => '1 Market Street',
                'shipping_city' => 'Portland',
                'shipping_postcode' => '97201',
                'shipping_country' => 'United States',
            ]);

        $response->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $variant->fresh()->stock_quantity);
        $this->assertSame($cart, session('cart'));
    }

    public function test_variant_without_price_override_uses_product_base_price_in_cart(): void
    {
        $category = Category::create([
            'name' => 'Knitwear',
            'slug' => 'knitwear',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Cardigan',
            'slug' => 'cardigan',
            'sku' => 'CARD-01',
            'price' => 120.00,
            'stock_quantity' => 0,
            'is_active' => true,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'size' => 'M',
            'color' => 'Beige',
            'price' => null,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $response = $this->postJson(route('store.cart.add', $product), [
            'quantity' => 2,
            'variant_id' => $variant->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonPath('subtotal', 240);
    }
}
