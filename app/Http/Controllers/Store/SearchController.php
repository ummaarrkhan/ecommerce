<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Return live instant search suggestions for navbar autocomplete.
     */
    public function suggestions(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        if (mb_strlen($term) < 2) {
            return response()->json([
                'query' => $term,
                'total' => 0,
                'categories' => [],
                'results' => [],
                'view_all_url' => null,
            ]);
        }

        // Matching categories
        $categories = Category::query()
            ->where('is_active', true)
            ->where('name', 'like', "%{$term}%")
            ->limit(3)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'url' => route('store.home', ['category_id' => $category->id]),
            ]);

        // Matching products
        $productsQuery = Product::query()
            ->with(['category', 'images', 'activeVariants'])
            ->where('is_active', true)
            ->where(function ($query) use ($term) {
                $query->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
                    ->orWhereHas('category', function ($cq) use ($term) {
                        $cq->where('name', 'like', "%{$term}%");
                    });
            });

        $totalCount = (clone $productsQuery)->count();

        $products = $productsQuery
            ->latest()
            ->limit(6)
            ->get();

        $results = $products->map(function (Product $product) {
            $firstImage = $product->all_images->first();
            $imageUrl = $firstImage['url'] ?? null;

            return [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'url' => route('store.products.show', $product->slug),
                'price' => (float) $product->price,
                'price_formatted' => '$'.number_format((float) $product->price, 2),
                'compare_at_price' => $product->compare_at_price ? (float) $product->compare_at_price : null,
                'compare_at_price_formatted' => $product->compare_at_price ? '$'.number_format((float) $product->compare_at_price, 2) : null,
                'on_sale' => $product->compare_at_price && (float) $product->compare_at_price > (float) $product->price,
                'category' => $product->category?->name,
                'image' => $imageUrl,
                'in_stock' => $product->available_stock > 0,
            ];
        });

        return response()->json([
            'query' => $term,
            'total' => $totalCount,
            'categories' => $categories,
            'results' => $results,
            'view_all_url' => route('store.home', ['search' => $term]),
        ]);
    }
}
