<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\OrderInvoiceController;
use App\Mail\OrderConfirmationMail;
use App\Mail\OrderStatusUpdatedMail;
use App\Mail\WelcomeMail;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin|admin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');
        Route::delete('/orders/by-status', [OrderController::class, 'destroyByStatus'])
            ->name('orders.destroyByStatus');
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');
        Route::delete('/orders/{order}', [OrderController::class, 'destroy'])
            ->name('orders.destroy');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.updateStatus');
        Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->name('orders.updatePaymentStatus');
        Route::patch('/orders/{order}/fulfillment', [OrderController::class, 'updateFulfillment'])
            ->name('orders.updateFulfillment');
        Route::get('/orders/{order}/invoice', [OrderInvoiceController::class, 'show'])
            ->name('orders.invoice');

        // Mailable HTML Previews
        Route::get('/mail-preview/order-confirmation', function () {
            $order = Order::with('items')->latest()->first();

            return $order
                ? new OrderConfirmationMail($order)
                : 'No orders available yet to preview. Place an order first.';
        })->name('mail-preview.order-confirmation');

        Route::get('/mail-preview/order-status', function () {
            $order = Order::with('items')->latest()->first();

            return $order
                ? new OrderStatusUpdatedMail($order)
                : 'No orders available yet to preview. Place an order first.';
        })->name('mail-preview.order-status');

        Route::get('/mail-preview/welcome', function () {
            $user = auth()->user() ?? User::first();

            return new WelcomeMail($user);
        })->name('mail-preview.welcome');
    });
