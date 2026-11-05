<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load([
            'category',
            'images',
            'activeVariants',
            'approvedReviews' => function ($query) {
                $query->with('user')->latest();
            },
        ]);

        return view('store.products.show', compact('product'));
    }
}
