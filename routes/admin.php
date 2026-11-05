<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\OrderInvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super-admin|admin'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::resource('categories', CategoryController::class);
        Route::resource('products', ProductController::class);
        Route::get('/orders', [OrderController::class, 'index'])
            ->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])
            ->name('orders.show');
        Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])
            ->name('orders.updateStatus');
        Route::patch('/orders/{order}/payment-status', [OrderController::class, 'updatePaymentStatus'])
            ->name('orders.updatePaymentStatus');
        Route::patch('/orders/{order}/fulfillment', [OrderController::class, 'updateFulfillment'])
            ->name('orders.updateFulfillment');
        Route::get('/orders/{order}/invoice', [OrderInvoiceController::class, 'show'])
            ->name('orders.invoice');
    });
