<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $orders = Order::where('user_id', $user->id)
            ->orWhere('customer_email', $user->email)
            ->with(['items.product'])
            ->latest()
            ->get();

        return view('store.account', compact('user', 'orders'));
    }
}
