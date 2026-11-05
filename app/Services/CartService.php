<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

class CartService
{
    /**
     * @param  array<int|string, mixed>  $cart
     * @return array<string, array{product_id: int, variant_id: int|null, quantity: int}>
     */
    public function normalize(array $cart): array
    {
        $items = [];

        foreach ($cart as $key => $cartItem) {
            $parts = explode(':', (string) $key, 2);
            $productId = (int) (is_array($cartItem)
                ? ($cartItem['product_id'] ?? $parts[0])
                : $parts[0]);
            $variantId = is_array($cartItem)
                ? ($cartItem['variant_id'] ?? ($parts[1] ?? null))
                : ($parts[1] ?? null);
            $quantity = (int) (is_array($cartItem)
                ? ($cartItem['quantity'] ?? 0)
                : $cartItem);

            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $variantId = $variantId === null || $variantId === ''
                ? null
                : (int) $variantId;
            $itemKey = $this->key($productId, $variantId);

            $items[$itemKey] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => ($items[$itemKey]['quantity'] ?? 0) + $quantity,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int|string, mixed>  $cart
     * @return array<int, array<string, mixed>>
     */
    public function items(array $cart): array
    {
        $normalized = $this->normalize($cart);
        $productIds = collect($normalized)
            ->pluck('product_id')
            ->unique()
            ->all();

        $products = Product::with(['category', 'variants'])
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        $items = [];

        foreach ($normalized as $key => $cartItem) {
            $product = $products->get($cartItem['product_id']);

            if (! $product) {
                continue;
            }

            $variant = $cartItem['variant_id'] === null
                ? null
                : $product->variants->firstWhere('id', $cartItem['variant_id']);
            $hasActiveVariants = $product->variants->contains(
                fn (ProductVariant $productVariant): bool => $productVariant->is_active
            );
            $stock = $variant?->stock_quantity ?? $product->stock_quantity;
            $unitPrice = $variant?->price ?? $product->price;
            $isAvailable = $product->is_active
                && ($cartItem['variant_id'] === null
                    ? ! $hasActiveVariants
                    : $variant?->is_active === true)
                && $cartItem['quantity'] <= $stock;

            $items[] = [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'variant_id' => $cartItem['variant_id'],
                'variant_name' => $variant?->display_name,
                'quantity' => $cartItem['quantity'],
                'unit_price' => (float) $unitPrice,
                'stock' => (int) $stock,
                'subtotal' => round((float) $unitPrice * $cartItem['quantity'], 2),
                'is_available' => $isAvailable,
            ];
        }

        return $items;
    }

    /**
     * @param  array<int|string, mixed>  $cart
     * @return array{items: array<int, array<string, mixed>>, count: int, subtotal: float}
     */
    public function summary(array $cart): array
    {
        $items = $this->items($cart);

        return [
            'items' => $items,
            'count' => array_sum(array_column($items, 'quantity')),
            'subtotal' => round(array_sum(array_column($items, 'subtotal')), 2),
        ];
    }

    public function key(int $productId, ?int $variantId): string
    {
        return $variantId === null
            ? (string) $productId
            : $productId.':'.$variantId;
    }
}
