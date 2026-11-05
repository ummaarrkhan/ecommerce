<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\OrderStatusUpdatedMail;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    // Show all orders
    public function index(Request $request)
    {
        $orders = Order::query();

        // Search orders by order number, customer name or email
        if ($request->search) {
            $search = $request->search;

            $orders->where(function ($query) use ($search) {
                $query->where('order_number', 'like', '%'.$search.'%')
                    ->orWhere('customer_name', 'like', '%'.$search.'%')
                    ->orWhere('customer_email', 'like', '%'.$search.'%');
            });
        }

        // Filter orders by status
        if ($request->status) {
            $orders->where('status', $request->status);
        }

        // Filter orders by payment status
        if ($request->payment_status) {
            $orders->where('payment_status', $request->payment_status);
        }

        // Get orders and show 10 orders per page
        $orders = $orders->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    // Show order details
    public function show(Order $order)
    {
        // Get order items
        $order->load('items');

        return view('admin.orders.show', compact('order'));
    }

    // Update order status
    public function updateStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in([
                    'pending',
                    'processing',
                    'shipped',
                    'completed',
                    'cancelled',
                ]),
            ],
        ]);

        $previousStatus = $order->status;

        // Save new order status
        $order->update([
            'status' => $validated['status'],
        ]);

        if ($previousStatus !== $validated['status']) {
            try {
                Mail::to($order->customer_email)->send(new OrderStatusUpdatedMail($order, $previousStatus));
            } catch (\Throwable $e) {
                Log::warning("Failed to dispatch order status email for order #{$order->order_number}: ".$e->getMessage());
            }
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Order status updated successfully.');
    }

    // Update payment status
    public function updatePaymentStatus(Request $request, Order $order)
    {
        $validated = $request->validate([
            'payment_status' => [
                'required',
                Rule::in([
                    'paid',
                    'unpaid',
                ]),
            ],
        ]);

        // Save new payment status
        $order->update([
            'payment_status' => $validated['payment_status'],
        ]);

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Payment status updated successfully.');
    }

    public function updateFulfillment(Request $request, Order $order)
    {
        $validated = $request->validate([
            'carrier' => ['nullable', 'string', 'max:100'],
            'tracking_number' => ['nullable', 'string', 'max:150'],
            'tracking_url' => ['nullable', 'url', 'max:500'],
            'estimated_delivery' => ['nullable', 'date'],
            'fulfillment_action' => ['nullable', 'string', 'in:save,ship,deliver'],
        ]);

        $updates = [
            'carrier' => $validated['carrier'] ?? $order->carrier,
            'tracking_number' => $validated['tracking_number'] ?? $order->tracking_number,
            'tracking_url' => $validated['tracking_url'] ?? $order->tracking_url,
            'estimated_delivery' => $validated['estimated_delivery'] ?? $order->estimated_delivery,
        ];

        $action = $validated['fulfillment_action'] ?? 'save';

        if ($action === 'ship') {
            $updates['status'] = 'shipped';
            $updates['shipped_at'] = now();
        } elseif ($action === 'deliver') {
            $updates['status'] = 'completed';
            $updates['delivered_at'] = now();
            if ($order->payment_method === 'cod' && $order->payment_status === 'unpaid') {
                $updates['payment_status'] = 'paid';
            }
        }

        $previousStatus = $order->status;
        $order->update($updates);

        if ($order->status !== $previousStatus || $action === 'ship' || $action === 'deliver') {
            try {
                Mail::to($order->customer_email)->send(new OrderStatusUpdatedMail($order, $previousStatus));
            } catch (\Throwable $e) {
                Log::warning("Failed to dispatch fulfillment update email for order #{$order->order_number}: ".$e->getMessage());
            }
        }

        return redirect()->route('admin.orders.show', $order)
            ->with('success', 'Fulfillment and tracking details updated successfully.');
    }
}
