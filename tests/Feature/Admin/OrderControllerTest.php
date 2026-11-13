<?php

namespace Tests\Feature\Admin;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');
    }

    private function createOrder(string $status = 'cancelled'): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Jane Customer',
            'customer_email' => 'jane@example.com',
            'customer_phone' => '+15551234567',
            'shipping_address' => '100 Market St',
            'shipping_city' => 'Chicago',
            'shipping_postcode' => '60601',
            'shipping_country' => 'United States',
            'subtotal' => 85.00,
            'shipping_cost' => 5.00,
            'total' => 90.00,
            'status' => $status,
            'payment_status' => 'unpaid',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_name' => 'Sample Product',
            'sku' => 'SMPL-01',
            'price' => 85.00,
            'quantity' => 1,
            'subtotal' => 85.00,
        ]);

        return $order;
    }

    public function test_admin_can_delete_cancelled_order(): void
    {
        $order = $this->createOrder('cancelled');

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_items', ['order_id' => $order->id]);
    }

    public function test_admin_can_delete_completed_order(): void
    {
        $order = $this->createOrder('completed');

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $order));

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_admin_cannot_delete_active_processing_or_shipped_order(): void
    {
        $processingOrder = $this->createOrder('processing');
        $shippedOrder = $this->createOrder('shipped');

        $response1 = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $processingOrder));

        $response1->assertSessionHas('error');
        $this->assertDatabaseHas('orders', ['id' => $processingOrder->id]);

        $response2 = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroy', $shippedOrder));

        $response2->assertSessionHas('error');
        $this->assertDatabaseHas('orders', ['id' => $shippedOrder->id]);
    }

    public function test_admin_can_delete_orders_by_status_in_bulk(): void
    {
        $order1 = $this->createOrder('cancelled');
        $order2 = $this->createOrder('cancelled');
        $shippedOrder = $this->createOrder('shipped');

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroyByStatus'), [
                'status' => 'cancelled',
            ]);

        $response->assertRedirect(route('admin.orders.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('orders', ['id' => $order1->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order2->id]);
        $this->assertDatabaseHas('orders', ['id' => $shippedOrder->id]);
    }

    public function test_bulk_delete_rejects_active_fulfillment_statuses(): void
    {
        $response = $this->actingAs($this->admin)
            ->delete(route('admin.orders.destroyByStatus'), [
                'status' => 'processing',
            ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_unauthenticated_or_unauthorized_user_cannot_delete_orders(): void
    {
        $order = $this->createOrder('cancelled');

        // Guest
        $guestResponse = $this->delete(route('admin.orders.destroy', $order));
        $guestResponse->assertRedirect(route('login'));

        // Customer
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $customerResponse = $this->actingAs($customer)
            ->delete(route('admin.orders.destroy', $order));
        $customerResponse->assertForbidden();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
    }
}
