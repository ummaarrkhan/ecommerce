<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderInvoiceController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        $user = $request->user();

        $isAuthorized = false;
        if ($user && ($user->hasAnyRole(['admin', 'super-admin']) || $user->id === $order->user_id || strtolower($user->email) === strtolower($order->customer_email))) {
            $isAuthorized = true;
        }

        if (! $isAuthorized && $request->filled('email') && strtolower((string) $request->query('email')) === strtolower($order->customer_email)) {
            $isAuthorized = true;
        }

        // Allow customer immediately after checkout
        if (! $isAuthorized && session('last_order') === $order->order_number) {
            $isAuthorized = true;
        }

        abort_unless($isAuthorized, 403, 'Unauthorized access to this invoice.');

        $order->load(['items.product', 'items.variant']);

        return view('orders.invoice', compact('order'));
    }
}
