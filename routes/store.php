<?php

use App\Http\Controllers\OrderInvoiceController;
use App\Http\Controllers\Store\AccountController;
use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\ReviewController;
use App\Http\Controllers\Store\SearchController;
use App\Http\Controllers\Store\WishlistController;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function (Request $request) {

    $query = Product::with(['category', 'activeVariants'])
        ->where('is_active', true);

    // Search products
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Filter by category (includes parent category and all its subcategories)
    if ($request->filled('category_id')) {
        $categoryIds = Category::getSelfAndDescendantIds($request->category_id);
        $query->whereIn('category_id', $categoryIds);
    }

    // Minimum price
    if ($request->filled('min_price') && is_numeric($request->min_price)) {
        $query->where('price', '>=', $request->min_price);
    }

    // Maximum price
    if ($request->filled('max_price') && is_numeric($request->max_price)) {
        $query->where('price', '<=', $request->max_price);
    }

    // Sorting
    switch ($request->input('sort', 'latest')) {
        case 'price_low':
            $query->orderBy('price', 'asc');
            break;

        case 'price_high':
            $query->orderBy('price', 'desc');
            break;

        case 'name':
            $query->orderBy('name', 'asc');
            break;

        default:
            $query->latest();
            break;
    }

    $products = $query->paginate(12)->withQueryString();
    $newArrivals = Product::with(['category.parent', 'activeVariants'])
        ->where('is_active', true)
        ->latest()
        ->limit(4)
        ->get();

    $bestSellers = Product::query()
        ->select('products.*')
        ->selectRaw('SUM(order_items.quantity) as total_sold')
        ->join('order_items', 'products.id', '=', 'order_items.product_id')
        ->join('orders', 'order_items.order_id', '=', 'orders.id')
        ->where('products.is_active', true)
        ->where('orders.status', 'completed')
        ->where('orders.payment_status', 'paid')
        ->groupBy('products.id')
        ->orderByDesc('total_sold')
        ->limit(4)
        ->with(['category.parent', 'activeVariants'])
        ->get();

    $categories = Category::with(['children' => function ($query) {
        $query->where('is_active', true)
            ->withCount(['products' => function ($pq) {
                $pq->where('is_active', true);
            }])
            ->orderBy('name');
    }])
        ->withCount(['products' => function ($pq) {
            $pq->where('is_active', true);
        }])
        ->whereNull('parent_id')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    return view('store.home', compact(
        'products',
        'categories',
        'bestSellers',
        'newArrivals'
    ));

})->name('store.home');

Route::get('/account', [AccountController::class, 'index'])
    ->middleware('auth')
    ->name('store.account');

Route::get('/search/suggestions', [SearchController::class, 'suggestions'])
    ->name('store.search.suggestions');

Route::get('/products/{product:slug}', [
    ProductController::class,
    'show',
])->name('store.products.show');

// Checkout routes
Route::get('/checkout', [CheckoutController::class, 'index'])
    ->name('store.checkout.index');

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('store.checkout.store');

Route::post('/checkout/stripe-intent', [CheckoutController::class, 'createStripeIntent'])
    ->name('store.checkout.stripe-intent');

Route::get('/checkout/success/{order}', function (string $order) {
    $order = Order::where('order_number', $order)->firstOrFail();

    return view('store.checkout.success', compact('order'));
})->name('store.checkout.success');

Route::get('/orders/{order:order_number}/invoice', [OrderInvoiceController::class, 'show'])
    ->name('store.orders.invoice');

Route::get('/cart', [CartController::class, 'index'])
    ->name('store.cart.index');

Route::get('/cart/drawer', [CartController::class, 'drawer'])
    ->name('store.cart.drawer');

Route::post('/cart/add/{product}', [CartController::class, 'add'])
    ->name('store.cart.add');

Route::patch('/cart/update/{product}', [CartController::class, 'update'])
    ->name('store.cart.update');

Route::delete('/cart/remove/{product}', [CartController::class, 'remove'])
    ->name('store.cart.remove');

// Wishlist routes
Route::get('/wishlist', [WishlistController::class, 'index'])
    ->name('store.wishlist.index');
Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])
    ->name('store.wishlist.toggle');
Route::delete('/wishlist/remove/{product}', [WishlistController::class, 'remove'])
    ->name('store.wishlist.remove');

// Product reviews route
Route::post('/products/{product:slug}/reviews', [ReviewController::class, 'store'])
    ->middleware('auth')
    ->name('store.reviews.store');
