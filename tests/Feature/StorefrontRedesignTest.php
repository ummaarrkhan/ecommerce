<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class StorefrontRedesignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_home_page_renders_successfully(): void
    {
        $category = Category::create([
            'name' => 'Menswear',
            'slug' => 'menswear',
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $category->id,
            'name' => 'Oxford Shirt',
            'slug' => 'oxford-shirt',
            'sku' => 'SHIRT-OXF-01',
            'description' => 'Classic tailored fit.',
            'price' => 75.00,
            'compare_at_price' => 95.00,
            'stock_quantity' => 15,
            'is_active' => true,
        ]);

        $response = $this->get(route('store.home'));

        $response->assertStatus(200);
        $response->assertSee('Oxford Shirt');
        $response->assertSee('Menswear');
    }

    public function test_all_items_are_paginated_eight_per_page(): void
    {
        $category = Category::create([
            'name' => 'Catalog',
            'slug' => 'catalog-pagination',
            'is_active' => true,
        ]);

        $this->createProducts($category, 'Catalog', 9);

        $firstPage = $this->get(route('store.home'));
        $firstPage->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->perPage() === 8
                && $products->count() === 8
                && $products->total() === 9
                && str_ends_with($products->url(2), '#featured-products'));

        $secondPage = $this->get(route('store.home', ['page' => 2]));
        $secondPage->assertOk()
            ->assertViewHas('products', fn ($products): bool => $products->perPage() === 8
                && $products->count() === 1
                && $products->currentPage() === 2
                && $products->total() === 9);
    }

    public function test_parent_and_subcategory_results_are_paginated_eight_per_page(): void
    {
        $parentCategory = Category::create([
            'name' => 'Menswear Pagination',
            'slug' => 'menswear-pagination',
            'is_active' => true,
        ]);

        $subcategory = Category::create([
            'name' => 'Shirts Pagination',
            'slug' => 'shirts-pagination',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
        ]);

        $this->createProducts($subcategory, 'Shirt', 9);

        foreach ([$parentCategory, $subcategory] as $category) {
            $response = $this->get(route('store.home', [
                'category_id' => $category->id,
                'page' => 2,
            ]));

            $response->assertOk()
                ->assertViewHas('products', fn ($products): bool => $products->perPage() === 8
                    && $products->count() === 1
                    && $products->currentPage() === 2
                    && $products->total() === 9
                    && str_contains($products->url(2), 'category_id='.$category->id)
                    && str_ends_with($products->url(2), '#featured-products'));

            $response->assertDontSee('category_id='.$category->id.'&amp;page=2');
        }
    }

    public function test_product_detail_page_renders_successfully(): void
    {
        $category = Category::create([
            'name' => 'Outerwear',
            'slug' => 'outerwear',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Wool Overcoat',
            'slug' => 'wool-overcoat',
            'sku' => 'COAT-WOOL-01',
            'description' => 'Fine merino blend.',
            'price' => 240.00,
            'compare_at_price' => 300.00,
            'stock_quantity' => 8,
            'is_active' => true,
        ]);

        $response = $this->get(route('store.products.show', $product->slug));

        $response->assertStatus(200);
        $response->assertSee('Wool Overcoat');
        $response->assertSee('240.00');
    }

    public function test_cart_page_renders_empty_state_and_with_items(): void
    {
        $response = $this->get(route('store.cart.index'));
        $response->assertStatus(200);
        $response->assertSee('Your cart is currently empty');

        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Belt',
            'slug' => 'leather-belt',
            'sku' => 'BELT-LTH-01',
            'price' => 45.00,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $addResponse = $this->post(route('store.cart.add', $product), [
            'quantity' => 2,
        ]);
        $addResponse->assertRedirect(route('store.cart.index'));

        $cartResponse = $this->get(route('store.cart.index'));
        $cartResponse->assertStatus(200);
        $cartResponse->assertSee('Leather Belt');
        $cartResponse->assertSee('90.00');
    }

    public function test_auth_pages_render_with_new_layout(): void
    {
        $loginResponse = $this->get(route('login'));
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('Sign in to manage your orders');

        $registerResponse = $this->get(route('register'));
        $registerResponse->assertStatus(200);
        $registerResponse->assertSee('Create Account');
    }

    public function test_authenticated_customer_can_access_account_page(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->get(route('store.account'));

        $response->assertStatus(200);
        $response->assertSee('My Account');
        $response->assertSee('John Doe');
    }

    public function test_admin_dashboard_accessible_to_super_admin(): void
    {
        $admin = User::factory()->create([
            'name' => 'Store Admin',
            'email' => 'admin@teststore.com',
        ]);
        $admin->assignRole('super-admin');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Store Overview');
    }

    public function test_user_can_toggle_and_view_wishlist(): void
    {
        $category = Category::create([
            'name' => 'Footwear',
            'slug' => 'footwear',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Derby Shoes',
            'slug' => 'derby-shoes',
            'sku' => 'SHOE-DERBY-01',
            'price' => 195.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        // Guest toggle
        $response = $this->post(route('store.wishlist.toggle', $product));
        $response->assertSessionHas('wishlist', [$product->id]);

        $wishlistView = $this->get(route('store.wishlist.index'));
        $wishlistView->assertStatus(200);
        $wishlistView->assertSee('Derby Shoes');

        // Remove from wishlist
        $removeResponse = $this->delete(route('store.wishlist.remove', $product));
        $removeResponse->assertRedirect();
        $this->assertEmpty(session('wishlist'));
    }

    public function test_authenticated_user_can_submit_product_review(): void
    {
        $user = User::factory()->create([
            'name' => 'Reviewer One',
            'email' => 'reviewer@example.com',
        ]);
        $user->assignRole('customer');

        $category = Category::create([
            'name' => 'Tops',
            'slug' => 'tops',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Knit Polo',
            'slug' => 'knit-polo',
            'sku' => 'POLO-KNIT-01',
            'price' => 85.00,
            'stock_quantity' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(route('store.reviews.store', $product->slug), [
            'rating' => 5,
            'title' => 'Fantastic quality',
            'comment' => 'The fabric is remarkably soft and the fit is tailored and flattering.',
        ]);

        $response->assertRedirect(route('store.products.show', $product->slug).'#reviews');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Fantastic quality',
        ]);

        $productPage = $this->get(route('store.products.show', $product->slug));
        $productPage->assertStatus(200);
        $productPage->assertSee('Fantastic quality');
        $productPage->assertSee('Reviewer One');
    }

    public function test_checkout_with_card_payment_processes_as_paid(): void
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
            'sku' => 'KNIT-CARD-01',
            'price' => 120.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('store.cart.add', $product), ['quantity' => 1]);

        $user = User::factory()->create(['name' => 'Sarah Connor', 'email' => 'sarah@example.com']);
        $user->assignRole('customer');

        $checkoutResponse = $this->actingAs($user)->post(route('store.checkout.store'), [
            'customer_name' => 'Sarah Connor',
            'customer_email' => 'sarah@example.com',
            'customer_phone' => '+1 555-0199',
            'shipping_address' => '742 Evergreen Terrace',
            'shipping_city' => 'Springfield',
            'shipping_postcode' => '97477',
            'shipping_country' => 'United States',
            'payment_method' => 'card',
            'card_holder' => 'Sarah Connor',
            'card_number' => '4242424242424242',
            'card_expiry' => '12/28',
            'card_cvc' => '123',
        ]);

        $order = Order::where('customer_email', 'sarah@example.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals('card', $order->payment_method);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertNotNull($order->payment_reference);

        $checkoutResponse->assertRedirect(route('store.checkout.success', ['order' => $order->order_number]));
    }

    public function test_guest_can_access_checkout_and_choose_options_or_place_order(): void
    {
        $category = Category::create([
            'name' => 'Home Decor',
            'slug' => 'home-decor',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Ceramic Vase',
            'slug' => 'ceramic-vase',
            'sku' => 'VASE-01',
            'price' => 35.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('store.cart.add', $product), ['quantity' => 1]);

        // Guest visits checkout page
        $response = $this->get(route('store.checkout.index'));
        $response->assertStatus(200);
        $response->assertSee('Continue as Guest');
        $response->assertSee('Sign In');
        $response->assertSee('Register');

        // Guest completes order without logging in
        $postResponse = $this->post(route('store.checkout.store'), [
            'customer_name' => 'Guest Shopper',
            'customer_email' => 'guest@example.com',
            'customer_phone' => '1234567890',
            'shipping_address' => '789 Guest Ave',
            'shipping_city' => 'Austin',
            'shipping_postcode' => '73301',
            'shipping_country' => 'United States',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('customer_email', 'guest@example.com')->first();
        $this->assertNotNull($order);
        $this->assertNull($order->user_id);
        $this->assertEquals('Guest Shopper', $order->customer_name);
        $postResponse->assertRedirect(route('store.checkout.success', ['order' => $order->order_number]));
    }

    public function test_logging_out_redirects_to_storefront_home(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('store.home'));
        $this->assertGuest();
    }

    public function test_signing_in_from_checkout_redirects_back_to_checkout(): void
    {
        $user = User::factory()->create([
            'email' => 'customer@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Visiting login with intended checkout URL sets session intended
        $this->get(route('login', ['intended' => route('store.checkout.index')]));

        $response = $this->post(route('login'), [
            'email' => 'customer@example.com',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('store.checkout.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_registering_from_checkout_redirects_back_to_checkout(): void
    {
        // Visiting register with intended checkout URL sets session intended
        $this->get(route('register', ['intended' => route('store.checkout.index')]));

        $response = $this->post(route('register'), [
            'name' => 'New Shopper',
            'email' => 'newshopper@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('store.checkout.index'));
        $this->assertAuthenticated();
        $this->assertEquals('newshopper@example.com', auth()->user()->email);
    }

    public function test_checkout_stripe_intent_endpoint_returns_client_secret(): void
    {
        $category = Category::create([
            'name' => 'Footwear',
            'slug' => 'footwear',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Boots',
            'slug' => 'leather-boots',
            'sku' => 'BOOTS-01',
            'price' => 150.00,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $this->post(route('store.cart.add', $product), ['quantity' => 1]);

        $user = User::factory()->create(['email' => 'stripe_buyer@example.com']);
        $user->assignRole('customer');

        $response = $this->actingAs($user)->postJson(route('store.checkout.stripe-intent'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'clientSecret',
            'paymentIntentId',
            'amount',
            'currency',
        ]);
        $this->assertStringStartsWith('pi_', $response->json('paymentIntentId'));
    }

    public function test_checkout_with_stripe_payment_intent_id_records_payment_reference(): void
    {
        $category = Category::create([
            'name' => 'Accessories',
            'slug' => 'accessories-stripe',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Belt',
            'slug' => 'leather-belt',
            'sku' => 'BELT-01',
            'price' => 45.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('store.cart.add', $product), ['quantity' => 1]);

        $user = User::factory()->create(['name' => 'Bruce Wayne', 'email' => 'bruce@waynecorp.com']);
        $user->assignRole('customer');

        $mockIntentId = 'pi_test_stripe_custom_mock_12345';

        $checkoutResponse = $this->actingAs($user)->post(route('store.checkout.store'), [
            'customer_name' => 'Bruce Wayne',
            'customer_email' => 'bruce@waynecorp.com',
            'customer_phone' => '+1 555-0100',
            'shipping_address' => '1007 Mountain Drive',
            'shipping_city' => 'Gotham',
            'shipping_postcode' => '10001',
            'shipping_country' => 'United States',
            'payment_method' => 'card',
            'stripe_payment_intent_id' => $mockIntentId,
        ]);

        $order = Order::where('customer_email', 'bruce@waynecorp.com')->first();
        $this->assertNotNull($order);
        $this->assertEquals('card', $order->payment_method);
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals($mockIntentId, $order->payment_reference);

        $checkoutResponse->assertRedirect(route('store.checkout.success', ['order' => $order->order_number]));
    }

    public function test_admin_can_update_order_fulfillment_and_tracking(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Boss', 'email' => 'boss@store.com']);
        $admin->assignRole('super-admin');

        $order = Order::create([
            'order_number' => 'ORD-TEST12345',
            'customer_name' => 'Jane Client',
            'customer_email' => 'client@test.com',
            'customer_phone' => '123456789',
            'shipping_address' => '123 Test St',
            'shipping_city' => 'Metropolis',
            'shipping_postcode' => '10001',
            'shipping_country' => 'USA',
            'subtotal' => 100.00,
            'shipping_cost' => 0.00,
            'total' => 100.00,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'payment_method' => 'cod',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.updateFulfillment', $order), [
            'carrier' => 'DHL Express',
            'tracking_number' => 'DHL987654321',
            'fulfillment_action' => 'ship',
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals('DHL Express', $order->carrier);
        $this->assertEquals('DHL987654321', $order->tracking_number);
        $this->assertEquals('shipped', $order->status);
        $this->assertNotNull($order->shipped_at);
    }

    public function test_authorized_user_can_view_order_invoice(): void
    {
        $user = User::factory()->create(['email' => 'buyer@example.com']);
        $user->assignRole('customer');

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-INV-999',
            'customer_name' => 'Buyer Person',
            'customer_email' => 'buyer@example.com',
            'customer_phone' => '123456789',
            'shipping_address' => '456 Sample Rd',
            'shipping_city' => 'Gotham',
            'shipping_postcode' => '10002',
            'shipping_country' => 'USA',
            'subtotal' => 50.00,
            'shipping_cost' => 0.00,
            'total' => 50.00,
            'status' => 'processing',
            'payment_status' => 'paid',
            'payment_method' => 'card',
        ]);

        $response = $this->actingAs($user)->get(route('store.orders.invoice', $order->order_number));
        $response->assertStatus(200);
        $response->assertSee('ORD-INV-999');
        $response->assertSee('Official Receipt');
    }

    public function test_search_suggestions_requires_minimum_two_characters(): void
    {
        $response = $this->getJson(route('store.search.suggestions', ['q' => 'a']));

        $response->assertStatus(200);
        $response->assertJson([
            'query' => 'a',
            'total' => 0,
            'results' => [],
        ]);
    }

    public function test_search_suggestions_returns_matching_products_and_categories(): void
    {
        $category = Category::create([
            'name' => 'Leather Bags',
            'slug' => 'leather-bags',
            'is_active' => true,
        ]);

        $activeProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Messenger Bag',
            'slug' => 'leather-messenger-bag',
            'sku' => 'BAG-LTH-01',
            'price' => 199.99,
            'stock_quantity' => 4,
            'is_active' => true,
        ]);

        $inactiveProduct = Product::create([
            'category_id' => $category->id,
            'name' => 'Leather Belt Inactive',
            'slug' => 'leather-belt-inactive',
            'sku' => 'BELT-OFF-01',
            'price' => 49.99,
            'stock_quantity' => 1,
            'is_active' => false,
        ]);

        $response = $this->getJson(route('store.search.suggestions', ['q' => 'Leather']));

        $response->assertStatus(200);
        $response->assertJsonPath('query', 'Leather');
        $response->assertJsonPath('total', 1);

        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals($activeProduct->id, $results[0]['id']);
        $this->assertEquals('Leather Messenger Bag', $results[0]['name']);
        $this->assertEquals('$199.99', $results[0]['price_formatted']);

        $categories = $response->json('categories');
        $this->assertCount(1, $categories);
        $this->assertEquals('Leather Bags', $categories[0]['name']);
    }

    public function test_filtering_by_parent_category_includes_all_subcategory_products(): void
    {
        $parentMen = Category::create([
            'name' => 'Men',
            'slug' => 'men-parent-test',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $subShirts = Category::create([
            'name' => 'Shirts',
            'slug' => 'shirts-sub-test',
            'parent_id' => $parentMen->id,
            'is_active' => true,
        ]);

        $subShoes = Category::create([
            'name' => 'Shoes',
            'slug' => 'shoes-sub-test',
            'parent_id' => $parentMen->id,
            'is_active' => true,
        ]);

        $otherCategory = Category::create([
            'name' => 'Women',
            'slug' => 'women-other-test',
            'parent_id' => null,
            'is_active' => true,
        ]);

        $shirtProduct = Product::create([
            'category_id' => $subShirts->id,
            'name' => 'Men Oxford Shirt',
            'slug' => 'men-oxford-shirt',
            'sku' => 'MEN-SHIRT-01',
            'price' => 59.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $shoeProduct = Product::create([
            'category_id' => $subShoes->id,
            'name' => 'Men Leather Loafer',
            'slug' => 'men-leather-loafer',
            'sku' => 'MEN-SHOE-01',
            'price' => 129.00,
            'stock_quantity' => 5,
            'is_active' => true,
        ]);

        $womenProduct = Product::create([
            'category_id' => $otherCategory->id,
            'name' => 'Women Silk Blouse',
            'slug' => 'women-silk-blouse',
            'sku' => 'WOMEN-BLOUSE-01',
            'price' => 89.00,
            'stock_quantity' => 7,
            'is_active' => true,
        ]);

        // When filtering by parent category "Men"
        $response = $this->get(route('store.home', ['category_id' => $parentMen->id]));

        $response->assertStatus(200);
        $response->assertSee('Men Oxford Shirt');
        $response->assertSee('Men Leather Loafer');
        $response->assertDontSee('Women Silk Blouse');

        // When filtering by specific subcategory "Shirts"
        $subResponse = $this->get(route('store.home', ['category_id' => $subShirts->id]));
        $subResponse->assertStatus(200);
        $subResponse->assertSee('Men Oxford Shirt');
        $subResponse->assertDontSee('Men Leather Loafer');
        $subResponse->assertDontSee('Women Silk Blouse');
    }

    private function createProducts(Category $category, string $namePrefix, int $count): void
    {
        for ($index = 1; $index <= $count; $index++) {
            Product::create([
                'category_id' => $category->id,
                'name' => "{$namePrefix} Product {$index}",
                'slug' => strtolower($namePrefix).'-pagination-product-'.$index,
                'sku' => strtoupper($namePrefix).'-PAGINATION-'.$index,
                'price' => 25.00,
                'stock_quantity' => 10,
                'is_active' => true,
            ]);
        }
    }
}
