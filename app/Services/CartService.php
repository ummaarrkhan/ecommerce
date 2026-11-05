<?php

namespace App\Services;

use App\Models\Product;

class CartService
{
    // Convert cart data into a standard format
    public function normalize(array $cart): array
    {
        $items = [];

        foreach ($cart as $key => $cartItem) {

            // Separate product ID and variant ID
            $parts = explode(':', (string) $key);

            // Get product ID
            if (is_array($cartItem)) {
                $productId = $cartItem['product_id'] ?? $parts[0];
            } else {
                $productId = $parts[0];
            }

            $productId = (int) $productId;

            // Get variant ID
            if (is_array($cartItem)) {
                $variantId = $cartItem['variant_id'] ?? ($parts[1] ?? null);
            } else {
                $variantId = $parts[1] ?? null;
            }

            // Get quantity
            if (is_array($cartItem)) {
                $quantity = (int) ($cartItem['quantity'] ?? 0);
            } else {
                $quantity = (int) $cartItem;
            }

            // Skip invalid products or quantities
            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            // Convert empty variant ID to null
            if ($variantId === null || $variantId === '') {
                $variantId = null;
            } else {
                $variantId = (int) $variantId;
            }

            // Create a unique key for the cart item
            if ($variantId === null) {
                $itemKey = (string) $productId;
            } else {
                $itemKey = $productId.':'.$variantId;
            }

            // Add item to cart
            if (isset($items[$itemKey])) {
                $items[$itemKey]['quantity'] += $quantity;
            } else {
                $items[$itemKey] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ];
            }
        }

        return $items;
    }

    // Get complete product information for cart items
    public function items(array $cart): array
    {
        $normalizedCart = $this->normalize($cart);

        // Get all product IDs
        $productIds = [];

        foreach ($normalizedCart as $cartItem) {
            if (! in_array($cartItem['product_id'], $productIds)) {
                $productIds[] = $cartItem['product_id'];
            }
        }

        // Get products from database
        $products = Product::with(['category', 'variants'])
            ->whereIn('id', $productIds)
            ->get();

        $items = [];

        foreach ($normalizedCart as $key => $cartItem) {

            // Find the product
            $product = null;

            foreach ($products as $storedProduct) {
                if ($storedProduct->id == $cartItem['product_id']) {
                    $product = $storedProduct;
                    break;
                }
            }

            // Skip if product does not exist
            if ($product === null) {
                continue;
            }

            // Find selected variant
            $variant = null;

            if ($cartItem['variant_id'] !== null) {
                foreach ($product->variants as $productVariant) {
                    if ($productVariant->id == $cartItem['variant_id']) {
                        $variant = $productVariant;
                        break;
                    }
                }
            }

            // Check if product has active variants
            $hasActiveVariants = false;

            foreach ($product->variants as $productVariant) {
                if ($productVariant->is_active) {
                    $hasActiveVariants = true;
                    break;
                }
            }

            // Get stock and price
            if ($variant !== null) {
                $stock = $variant->stock_quantity;
                $unitPrice = $variant->price;
            } else {
                $stock = $product->stock_quantity;
                $unitPrice = $product->price;
            }

            // Check product availability
            $isAvailable = true;

            if (! $product->is_active) {
                $isAvailable = false;
            }

            if ($cartItem['variant_id'] === null && $hasActiveVariants) {
                $isAvailable = false;
            }

            if ($cartItem['variant_id'] !== null) {
                if ($variant === null || ! $variant->is_active) {
                    $isAvailable = false;
                }
            }

            if ($cartItem['quantity'] > $stock) {
                $isAvailable = false;
            }

            // Calculate subtotal
            $subtotal = round($unitPrice * $cartItem['quantity'], 2);

            // Add product information to cart
            $items[] = [
                'key' => $key,
                'product' => $product,
                'variant' => $variant,
                'variant_id' => $cartItem['variant_id'],
                'variant_name' => $variant !== null ? $variant->display_name : null,
                'quantity' => $cartItem['quantity'],
                'unit_price' => (float) $unitPrice,
                'stock' => (int) $stock,
                'subtotal' => $subtotal,
                'is_available' => $isAvailable,
            ];
        }

        return $items;
    }

    // Get cart summary
    public function summary(array $cart): array
    {
        $items = $this->items($cart);

        $totalQuantity = 0;
        $totalPrice = 0;

        foreach ($items as $item) {
            $totalQuantity += $item['quantity'];
            $totalPrice += $item['subtotal'];
        }

        return [
            'items' => $items,
            'count' => $totalQuantity,
            'subtotal' => round($totalPrice, 2),
        ];
    }

    // Create a unique cart item key
    public function key(int $productId, ?int $variantId): string
    {
        if ($variantId === null) {
            return (string) $productId;
        }

        return $productId.':'.$variantId;
    }
}
