<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private readonly CartService $cartService) {}

    public function index(Request $request): View
    {
        $summary = $this->cartService->summary(
            $request->session()->get('cart', [])
        );
        $items = $summary['items'];
        $total = $summary['subtotal'];

        return view('store.cart.index', compact('items', 'total'));
    }

    public function drawer(Request $request): JsonResponse
    {
        return $this->drawerResponse(
            $this->cartService->summary($request->session()->get('cart', []))
        );
    }

    public function add(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        if (! $product->is_active) {
            return $this->unavailableResponse($request);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);

        $product->load('variants');
        $hasActiveVariants = $product->variants->contains(
            fn (ProductVariant $variant): bool => $variant->is_active
        );
        $variant = null;

        if ($hasActiveVariants && empty($validated['variant_id'])) {
            throw ValidationException::withMessages([
                'variant_id' => 'Please choose your product options before adding it to your cart.',
            ]);
        }

        if (! empty($validated['variant_id'])) {
            $variant = $product->variants->firstWhere('id', $validated['variant_id']);

            if (! $variant || ! $variant->is_active) {
                throw ValidationException::withMessages([
                    'variant_id' => 'The selected product option is unavailable.',
                ]);
            }
        }

        $stock = $variant?->stock_quantity ?? $product->stock_quantity;

        if ($stock < 1) {
            return $this->unavailableResponse($request);
        }

        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );
        $key = $this->cartService->key($product->id, $variant?->id);
        $currentQuantity = (int) ($cart[$key]['quantity'] ?? 0);
        $newQuantity = $currentQuantity + (int) $validated['quantity'];

        if ($newQuantity > $stock) {
            throw ValidationException::withMessages([
                'quantity' => 'The requested quantity exceeds available stock.',
            ]);
        }

        $cart[$key] = [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $newQuantity,
        ];
        $request->session()->put('cart', $cart);

        $summary = $this->cartService->summary($cart);

        if ($request->expectsJson()) {
            return $this->drawerResponse($summary, 'Product added to your cart.');
        }

        return redirect()
            ->route('store.cart.index')
            ->with('success', 'Product added to your cart.');
    }

    public function update(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
        ]);
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );
        $variantId = empty($validated['variant_id'])
            ? null
            : (int) $validated['variant_id'];
        $key = $this->cartService->key($product->id, $variantId);

        if (! array_key_exists($key, $cart)) {
            return $this->cartErrorResponse(
                $request,
                'This product option is not in your cart.'
            );
        }

        $product->load('variants');
        $variant = $variantId === null
            ? null
            : $product->variants->firstWhere('id', $variantId);
        $hasActiveVariants = $product->variants->contains(
            fn (ProductVariant $productVariant): bool => $productVariant->is_active
        );

        if (
            ! $product->is_active
            || ($variantId !== null && (! $variant || ! $variant->is_active))
            || ($variantId === null && $hasActiveVariants)
            || $validated['quantity'] > ($variant?->stock_quantity ?? $product->stock_quantity)
        ) {
            return $this->cartErrorResponse(
                $request,
                'The requested quantity is not available.'
            );
        }

        $cart[$key]['quantity'] = (int) $validated['quantity'];
        $request->session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return $this->drawerResponse(
                $this->cartService->summary($cart),
                'Cart updated successfully.'
            );
        }

        return redirect()
            ->route('store.cart.index')
            ->with('success', 'Cart updated successfully.');
    }

    public function remove(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['nullable', 'integer'],
        ]);
        $cart = $this->cartService->normalize(
            $request->session()->get('cart', [])
        );
        $variantId = empty($validated['variant_id'])
            ? null
            : (int) $validated['variant_id'];
        unset($cart[$this->cartService->key($product->id, $variantId)]);

        $request->session()->put('cart', $cart);

        if ($request->expectsJson()) {
            return $this->drawerResponse(
                $this->cartService->summary($cart),
                'Product removed from your cart.'
            );
        }

        return redirect()
            ->route('store.cart.index')
            ->with('success', 'Product removed from your cart.');
    }

    /**
     * @param  array{items: array<int, array<string, mixed>>, count: int, subtotal: float}  $summary
     */
    private function drawerResponse(array $summary, string $message = ''): JsonResponse
    {
        return response()->json([
            'items_html' => view('store.cart.drawer-items', [
                'items' => $summary['items'],
            ])->render(),
            'count' => $summary['count'],
            'subtotal' => $summary['subtotal'],
            'message' => $message,
            'cart_items' => array_map(
                static fn (array $item): array => [
                    'product_id' => $item['product']->id,
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'stock' => $item['stock'],
                ],
                $summary['items']
            ),
        ]);
    }

    private function unavailableResponse(Request $request): JsonResponse|RedirectResponse
    {
        return $this->cartErrorResponse(
            $request,
            'This product is currently unavailable.'
        );
    }

    private function cartErrorResponse(Request $request, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return back()->with('error', $message);
    }
}
