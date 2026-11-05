<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'comment' => ['required', 'string', 'min:5', 'max:5000'],
        ]);

        $isVerifiedPurchase = $user->hasPurchased($product);

        Review::updateOrCreate(
            [
                'user_id' => $user->id,
                'product_id' => $product->id,
            ],
            [
                'rating' => $validated['rating'],
                'title' => $validated['title'] ?? null,
                'comment' => $validated['comment'],
                'is_verified_purchase' => $isVerifiedPurchase,
                'is_approved' => true,
            ]
        );

        return redirect()
            ->to(route('store.products.show', $product->slug).'#reviews')
            ->with('success', 'Thank you! Your product review has been submitted.');
    }
}
