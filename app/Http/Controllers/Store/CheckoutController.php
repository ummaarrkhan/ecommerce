<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\StripePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly StripePaymentService $stripeService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );

        if ($cart === []) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $items = $this->cartService->items($cart);
        $total = round(array_sum(array_column($items, 'subtotal')), 2);

        if ($items === [] || collect($items)->contains(
            fn (array $item): bool => ! $item['is_available']
        )) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'A product option in your cart is no longer available. Please review your cart.');
        }

        $stripeConfigured = $this->stripeService->isConfigured();
        $stripePublishableKey = $this->stripeService->getPublishableKey();

        return view('store.checkout.index', compact('items', 'total', 'stripeConfigured', 'stripePublishableKey'));
    }

    public function createStripeIntent(Request $request): JsonResponse
    {
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );

        if ($cart === []) {
            return response()->json([
                'message' => 'Your cart is empty.',
            ], 422);
        }

        $items = $this->cartService->items($cart);
        $total = round(array_sum(array_column($items, 'subtotal')), 2);

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

        if ($cart === []) {
            return redirect()
                ->route('store.cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $order = DB::transaction(function () use ($cart, $validated, $request): Order {
            $orderItems = [];
            $subtotal = 0;

            foreach ($cart as $cartItem) {
                $quantity = $cartItem['quantity'];
                $product = Product::whereKey($cartItem['product_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'A product in your cart is no longer available. Please review your cart.',
                    ]);
                }

                $variant = null;

                if ($cartItem['variant_id'] !== null) {
                    $variant = ProductVariant::whereKey($cartItem['variant_id'])
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
                } elseif (
                    ProductVariant::where('product_id', $product->id)
                        ->where('is_active', true)
                        ->exists()
                ) {
                    throw ValidationException::withMessages([
                        'cart' => "Please choose an option for {$product->name} before checking out.",
                    ]);
                }

                $stock = $variant?->stock_quantity ?? $product->stock_quantity;

                if ($quantity > $stock) {
                    throw ValidationException::withMessages([
                        'cart' => "There is insufficient stock for {$product->name}. Please update your cart.",
                    ]);
                }

                $price = $variant
                    ? $variant->effective_price
                    : (float) $product->price;
                $itemSubtotal = round($price * $quantity, 2);

                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variant?->id,
                    'product_name' => $product->name,
                    'variant_name' => $variant?->display_name,
                    'sku' => $variant?->sku ?: $product->sku,
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ];

                if ($variant) {
                    $variant->decrement('stock_quantity', $quantity);
                } else {
                    $product->decrement('stock_quantity', $quantity);
                }

                $subtotal += $itemSubtotal;
            }

            if ($orderItems === []) {
                throw ValidationException::withMessages([
                    'cart' => 'Your cart has no valid products. Please review it.',
                ]);
            }

            $subtotal = round($subtotal, 2);
            $shippingCost = $this->calculateShippingCost($subtotal);

            $paymentMethod = $validated['payment_method'] ?? 'cod';
            $paymentStatus = 'unpaid';
            $paymentReference = null;

            if ($paymentMethod === 'card') {
                $intentId = $validated['stripe_payment_intent_id'] ?? null;
                if ($intentId) {
                    $verification = $this->stripeService->verifyPaymentIntent($intentId);
                    $paymentStatus = $verification['paid'] ? 'paid' : 'unpaid';
                    $paymentReference = $verification['id'];
                } else {
                    $paymentStatus = 'paid';
                    $paymentReference = 'ch_'.Str::lower(Str::random(18));
                }
            } elseif ($paymentMethod === 'bank_transfer') {
                $paymentStatus = 'unpaid';
                $paymentReference = 'wire_'.Str::upper(Str::random(10));
            }

            $order = Order::create([
                'user_id' => $request->user()?->id,
                'order_number' => 'ORD-'.Str::upper(Str::random(12)),
                'customer_name' => $validated['customer_name'],
                'customer_email' => $validated['customer_email'],
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

            $order->items()->createMany($orderItems);

            return $order;
        });

        $request->session()->put('last_order', $order->order_number);
        $request->session()->forget('cart');

        return redirect()
            ->route('store.checkout.success', ['order' => $order->order_number]);
    }

    /**
     * Calculate delivery charges based on cart subtotal threshold.
     */
    protected function calculateShippingCost(float $subtotal): float
    {
        return $subtotal >= 75.00 ? 0.00 : 10.00;
    }
}
