<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    // Show admin dashboard
    public function index()
    {
        // Count all products
        $totalProducts = Product::count();

        // Count active products
        $activeProducts = Product::where('is_active', true)->count();

        // Count all categories
        $totalCategories = Category::count();

        // Count all orders
        $totalOrders = Order::count();

        // Count orders by status
        $pendingOrders = Order::where('status', 'pending')->count();

        $processingOrders = Order::where('status', 'processing')->count();

        $shippedOrders = Order::where('status', 'shipped')->count();

        $completedOrders = Order::where('status', 'completed')->count();

        // Calculate total revenue from paid orders
        $totalRevenue = Order::where('payment_status', 'paid')
            ->sum('total');

        // Get the latest 5 orders
        $recentOrders = Order::latest()
            ->take(5)
            ->get();

        // Send all data to dashboard page
        return view('admin.dashboard', compact(
            'totalProducts',
            'activeProducts',
            'totalCategories',
            'totalOrders',
            'pendingOrders',
            'processingOrders',
            'shippedOrders',
            'completedOrders',
            'totalRevenue',
            'recentOrders'
        ));
    }
}
