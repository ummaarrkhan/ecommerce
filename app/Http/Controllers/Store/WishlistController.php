<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WishlistController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->user()) {
            $products = $request->user()
                ->wishlistProducts()
                ->with(['category', 'activeVariants'])
                ->where('is_active', true)
                ->get();
        } else {
            $ids = (array) $request->session()->get('wishlist', []);
            $products = Product::whereIn('id', $ids)
                ->with(['category', 'activeVariants'])
                ->where('is_active', true)
                ->get();
        }

        return view('store.wishlist.index', compact('products'));
    }

    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $existing = Wishlist::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $existing->delete();
                $added = false;
                $message = 'Removed "'.$product->name.'" from your wishlist.';
            } else {
                Wishlist::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                ]);
                $added = true;
                $message = 'Added "'.$product->name.'" to your wishlist!';
            }

            $count = Wishlist::where('user_id', $user->id)->count();
        } else {
            $wishlist = (array) $request->session()->get('wishlist', []);

            if (in_array($product->id, $wishlist)) {
                $wishlist = array_values(array_diff($wishlist, [$product->id]));
                $added = false;
                $message = 'Removed "'.$product->name.'" from your wishlist.';
            } else {
                $wishlist[] = $product->id;
                $added = true;
                $message = 'Added "'.$product->name.'" to your wishlist!';
            }

            $request->session()->put('wishlist', $wishlist);
            $count = count($wishlist);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'added' => $added,
                'count' => $count,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function remove(Request $request, Product $product): RedirectResponse
    {
        if ($request->user()) {
            Wishlist::where('user_id', $request->user()->id)
                ->where('product_id', $product->id)
                ->delete();
        } else {
            $wishlist = (array) $request->session()->get('wishlist', []);
            $wishlist = array_values(array_diff($wishlist, [$product->id]));
            $request->session()->put('wishlist', $wishlist);
        }

        return back()->with('success', 'Removed "'.$product->name.'" from your wishlist.');
    }
}
