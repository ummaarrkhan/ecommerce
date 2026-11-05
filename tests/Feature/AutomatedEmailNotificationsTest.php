<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\WelcomeMail;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AutomatedEmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'web']);
    }

    public function test_order_confirmation_email_is_sent_on_successful_checkout(): void
    {
        Mail::fake();

        $category = Category::create([
            'name' => 'Suits',
            'slug' => 'suits',
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Classic Navy Suit',
            'slug' => 'classic-navy-suit',
            'sku' => 'SUIT-NAVY-01',
            'price' => 150.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        $this->post(route('store.cart.add', $product), ['quantity' => 1]);

        $response = $this->post(route('store.checkout.store'), [
            'customer_name' => 'John Shopper',
            'customer_email' => 'john.shopper@example.com',
            'customer_phone' => '+123456789',
            'shipping_address' => '456 Fashion Blvd',
            'shipping_city' => 'New York',
            'shipping_postcode' => '10001',
            'shipping_country' => 'United States',
            'payment_method' => 'cod',
        ]);

        $order = Order::where('customer_email', 'john.shopper@example.com')->first();
        $this->assertNotNull($order);
        $response->assertRedirect(route('store.checkout.success', ['order' => $order->order_number]));

        // Assert OrderConfirmationMail was sent to customer's email
        Mail::assertSent(OrderConfirmationMail::class, function (OrderConfirmationMail $mail) use ($order) {
            return $mail->hasTo('john.shopper@example.com') &&
                   $mail->order->id === $order->id;
        });
    }

    public function test_order_status_update_email_is_sent_when_admin_updates_status(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order = Order::create([
            'order_number' => 'ORD-TEST12345',
            'customer_name' => 'Sarah Connor',
            'customer_email' => 'sarah@example.com',
            'customer_phone' => '1234567890',
            'shipping_address' => '123 Resistance Way',
            'shipping_city' => 'Los Angeles',
            'shipping_postcode' => '90001',
            'shipping_country' => 'United States',
            'subtotal' => 100.00,
            'shipping_cost' => 0.00,
            'total' => 100.00,
            'status' => 'pending',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.updateStatus', $order), [
            'status' => 'shipped',
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        Mail::assertSent(OrderStatusUpdatedMail::class, function (OrderStatusUpdatedMail $mail) {
            return $mail->hasTo('sarah@example.com') &&
                   $mail->order->status === 'shipped';
        });
    }

    public function test_order_status_update_email_is_sent_when_admin_fulfills_order_with_tracking(): void
    {
        Mail::fake();

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $order = Order::create([
            'order_number' => 'ORD-TRACK123',
            'customer_name' => 'Bruce Wayne',
            'customer_email' => 'bruce@wayne.com',
            'customer_phone' => '5551234567',
            'shipping_address' => '1007 Mountain Drive',
            'shipping_city' => 'Gotham',
            'shipping_postcode' => '10001',
            'shipping_country' => 'United States',
            'subtotal' => 250.00,
            'shipping_cost' => 0.00,
            'total' => 250.00,
            'status' => 'processing',
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.updateFulfillment', $order), [
            'carrier' => 'DHL Express',
            'tracking_number' => 'DHL-99281726',
            'tracking_url' => 'https://www.dhl.com',
            'fulfillment_action' => 'ship',
        ]);

        $response->assertRedirect(route('admin.orders.show', $order));

        $order->refresh();
        $this->assertEquals('shipped', $order->status);
        $this->assertEquals('DHL Express', $order->carrier);

        Mail::assertSent(OrderStatusUpdatedMail::class, function (OrderStatusUpdatedMail $mail) {
            return $mail->hasTo('bruce@wayne.com') &&
                   $mail->order->carrier === 'DHL Express' &&
                   $mail->order->tracking_number === 'DHL-99281726';
        });
    }

    public function test_welcome_email_is_sent_upon_user_registration(): void
    {
        Mail::fake();

        $response = $this->post(route('register'), [
            'name' => 'Alice Green',
            'email' => 'alice@example.com',
            'password' => 'secretPassword123!',
            'password_confirmation' => 'secretPassword123!',
        ]);

        $response->assertRedirect(route('store.account'));

        Mail::assertSent(WelcomeMail::class, function (WelcomeMail $mail) {
            return $mail->hasTo('alice@example.com') &&
                   $mail->user->name === 'Alice Green';
        });
    }

    public function test_order_confirmation_mail_renders_html_properly(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-RENDER-01',
            'customer_name' => 'Jane Tester',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '987654321',
            'shipping_address' => '789 Test Rd',
            'shipping_city' => 'Chicago',
            'shipping_postcode' => '60601',
            'shipping_country' => 'United States',
            'subtotal' => 95.00,
            'shipping_cost' => 0.00,
            'total' => 95.00,
            'status' => 'pending',
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);

        $order->items()->create([
            'product_name' => 'Embroidered Cotton Dress',
            'variant_name' => 'Medium / Emerald',
            'sku' => 'DRESS-01',
            'price' => 95.00,
            'quantity' => 1,
            'subtotal' => 95.00,
        ]);

        $mailable = new OrderConfirmationMail($order);
        $rendered = $mailable->render();

        $this->assertStringContainsString('ORD-RENDER-01', $rendered);
        $this->assertStringContainsString('Jane Tester', $rendered);
        $this->assertStringContainsString('Embroidered Cotton Dress', $rendered);
        $this->assertStringContainsString('Medium / Emerald', $rendered);
        $this->assertStringContainsString('95.00', $rendered);
        $this->assertStringContainsString('FREE', $rendered);
    }

    public function test_order_status_mail_renders_tracking_details(): void
    {
        $order = Order::create([
            'order_number' => 'ORD-TRACK-RENDER',
            'customer_name' => 'Mark Spector',
            'customer_email' => 'mark@example.com',
            'customer_phone' => '1122334455',
            'shipping_address' => '55 Moon Way',
            'shipping_city' => 'Cairo',
            'shipping_postcode' => '11511',
            'shipping_country' => 'Egypt',
            'subtotal' => 120.00,
            'shipping_cost' => 10.00,
            'total' => 130.00,
            'status' => 'shipped',
            'carrier' => 'FedEx Express',
            'tracking_number' => 'FX-77889900',
            'payment_method' => 'cod',
            'payment_status' => 'unpaid',
        ]);

        $order->items()->create([
            'product_name' => 'Linen Tunic',
            'price' => 120.00,
            'quantity' => 1,
            'subtotal' => 120.00,
        ]);

        $mailable = new OrderStatusUpdatedMail($order);
        $rendered = $mailable->render();

        $this->assertStringContainsString('ORD-TRACK-RENDER', $rendered);
        $this->assertStringContainsString('Shipped', $rendered);
        $this->assertStringContainsString('FedEx Express', $rendered);
        $this->assertStringContainsString('FX-77889900', $rendered);
    }
}
