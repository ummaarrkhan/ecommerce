<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmationMail;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    protected $cartService;

    protected $stripeService;

    public function __construct(CartService $cartService, StripePaymentService $stripeService)
    {
        $this->cartService = $cartService;
        $this->stripeService = $stripeService;
    }

    // Show checkout page
    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );

        if (empty($cart)) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $items = $this->cartService->items($cart);

        $total = 0;

        foreach ($items as $item) {
            $total = $total + $item['subtotal'];
        }

        $total = round($total, 2);

        // Check if all cart products are available
        $hasUnavailableProduct = false;

        foreach ($items as $item) {
            if (! $item['is_available']) {
                $hasUnavailableProduct = true;
                break;
            }
        }

        if (empty($items) || $hasUnavailableProduct) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'A product option in your cart is no longer available. Please review your cart.');
        }

        $stripeConfigured = $this->stripeService->isConfigured();

        $stripePublishableKey = $this->stripeService->getPublishableKey();

        return view('store.checkout.index', compact(
            'items',
            'total',
            'stripeConfigured',
            'stripePublishableKey'
        ));
    }

    // Create Stripe payment intent
    public function createStripeIntent(Request $request): JsonResponse
    {
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );

        if (empty($cart)) {
            return response()->json([
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $items = $this->cartService->items($cart);

        $total = 0;

        foreach ($items as $item) {
            $total = $total + $item['subtotal'];
        }

        $total = round($total, 2);

        if ($total <= 0) {
            return response()->json([
                'message' => 'Cart amount must be greater than zero.',
            ], 422);
        }

        try {
            $shippingCost = $this->calculateShippingCost($total);

            $grandTotal = round($total + $shippingCost, 2);

            $intent = $this->stripeService->createPaymentIntent(
                $grandTotal,
                metadata: [
                    'customer_email' => $request->user()?->email,
                    'user_id' => $request->user()?->id,
                    'subtotal' => $total,
                    'shipping_cost' => $shippingCost,
                ]
            );

            return response()->json([
                'clientSecret' => $intent['client_secret'],
                'paymentIntentId' => $intent['id'],
                'publishableKey' => $this->stripeService->getPublishableKey(),
                'isLive' => $this->stripeService->isConfigured(),
                'amount' => $grandTotal,
                'shipping_cost' => $shippingCost,
                'currency' => config('services.stripe.currency', 'usd'),
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to initialize payment gateway: '.$e->getMessage(),
            ], 500);
        }
    }

    // Save order
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'shipping_address' => ['required', 'string', 'max:255'],
            'shipping_city' => ['required', 'string', 'max:100'],
            'shipping_postcode' => ['required', 'string', 'max:30'],
            'shipping_country' => ['required', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'in:cod,card,bank_transfer'],
            'stripe_payment_intent_id' => ['nullable', 'string', 'max:255'],
            'card_holder' => ['nullable', 'string', 'max:255'],
            'card_number' => ['nullable', 'string', 'max:30'],
            'card_expiry' => ['nullable', 'string', 'max:10'],
            'card_cvc' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );

        if (empty($cart)) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'Your cart is empty.');
        }

        // Create order and update stock inside a database transaction
        $order = DB::transaction(function () use ($cart, $validated, $request) {

            $orderItems = [];
            $subtotal = 0;

            foreach ($cart as $cartItem) {

                $quantity = $cartItem['quantity'];

                // Find product using Eloquent ORM
                $product = Product::where('id', $cartItem['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'A product in your cart is no longer available. Please review your cart.',
                    ]);
                }

                $variant = null;

                // Check whether the cart item has a selected variant
                if ($cartItem['variant_id'] !== null) {

                    $variant = ProductVariant::where('id', $cartItem['variant_id'])
                        ->where('product_id', $product->id)
                        ->where('is_active', true)
                        ->lockForUpdate()
                        ->first();

                    if (! $variant) {
                        throw ValidationException::withMessages([
                            'cart' => "A selected option for {$product->name} is no longer available. Please review your cart.",
                        ]);
                    }

                    $variant->setRelation('product', $product);

                } else {

                    // Check if this product has active variants
                    $hasVariants = ProductVariant::where('product_id', $product->id)
                        ->where('is_active', true)
                        ->exists();

                    if ($hasVariants) {
                        throw ValidationException::withMessages([
                            'cart' => "Please choose an option for {$product->name} before checking out.",
                        ]);
                    }
                }

                // Get available stock
                if ($variant) {
                    $stock = $variant->stock_quantity;
                } else {
                    $stock = $product->stock_quantity;
                }

                if ($quantity > $stock) {
                    throw ValidationException::withMessages([
                        'cart' => "There is insufficient stock for {$product->name}. Please update your cart.",
                    ]);
                }

                // Get product price
                if ($variant) {
                    $price = $variant->effective_price;
                } else {
                    $price = (float) $product->price;
                }

                $itemSubtotal = round($price * $quantity, 2);

                // Prepare order item details
                $orderItem = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant ? $variant->id : null,
                    'product_name' => $product->name,
                    'variant_name' => $variant ? $variant->display_name : null,
                    'sku' => $variant && $variant->sku ? $variant->sku : $product->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ];

                $orderItems[] = $orderItem;

                // Reduce product stock
                if ($variant) {
                    $variant->decrement('stock_quantity', $quantity);
                } else {
                    $product->decrement('stock_quantity', $quantity);
                }

                $subtotal = $subtotal + $itemSubtotal;
            }

            if (empty($orderItems)) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart has no valid products. Please review it.',
                ]);
            }

            $subtotal = round($subtotal, 2);

            $shippingCost = $this->calculateShippingCost($subtotal);

            // Set payment information
            $paymentMethod = $validated['payment_method'] ?? 'cod';

            $paymentStatus = 'unpaid';

            $paymentReference = null;

            if ($paymentMethod == 'card') {

                $intentId = $validated['stripe_payment_intent_id'] ?? null;

                if ($this->stripeService->isConfigured() && ! app()->environment('testing')) {
                    if (! $intentId) {
                        throw ValidationException::withMessages([
                            'stripe_payment_intent_id' => 'Payment authorization failed. Please complete card details and try again.',
                        ]);
                    }

                    $verification = $this->stripeService->verifyPaymentIntent($intentId);

                    if (! $verification['paid']) {
                        throw ValidationException::withMessages([
                            'stripe_payment_intent_id' => 'Card payment has not been confirmed. Please try again.',
                        ]);
                    }

                    $paymentStatus = 'paid';
                    $paymentReference = $verification['id'];
                } else {
                    // Test simulation mode when live Stripe keys are not configured or running in testing environment
                    $paymentStatus = 'paid';
                    $paymentReference = $intentId ?: 'ch_sim_'.Str::lower(Str::random(18));
                }

            } elseif ($paymentMethod == 'bank_transfer') {

                $paymentStatus = 'unpaid';

                $paymentReference = 'wire_'.Str::upper(Str::random(10));
            }

            // Get customer email
            if ($request->user()) {
                $customerEmail = $request->user()->email;
            } else {
                $customerEmail = $validated['customer_email'];
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user() ? $request->user()->id : null,
                'order_number' => 'ORD-'.Str::upper(Str::random(12)),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $customerEmail,
                'customer_phone' => $validated['customer_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'],
                'shipping_postcode' => $validated['shipping_postcode'],
                'shipping_country' => $validated['shipping_country'],
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'total' => $subtotal + $shippingCost,
                'status' => 'pending',
                'payment_method' => $paymentMethod,
                'payment_status' => $paymentStatus,
                'payment_reference' => $paymentReference,
                'estimated_delivery' => now()->addDays(3)->toDateString(),
                'notes' => $validated['notes'] ?? null,
            ]);

            // Save all order items
            $order->items()->createMany($orderItems);

            return $order;
        });

        // Send order confirmation email
        try {

            Mail::to($order->customer_email)
                ->send(new OrderConfirmationMail($order));

        } catch (\Throwable $e) {

            Log::warning(
                "Failed to dispatch order confirmation email for order #{$order->order_number}: "
                .$e->getMessage()
            );
        }

        // Save order number and clear cart
        $request->session()->put('last_order', $order->order_number);

        $request->session()->forget('cart');

        return redirect()->route('store.checkout.success', [
            'order' => $order->order_number,
        ]);
    }

    // Calculate shipping cost
    protected function calculateShippingCost(float $subtotal): float
    {
        if ($subtotal >= 75.00) {
            return 0.00;
        }

        return 10.00;
    }
}
