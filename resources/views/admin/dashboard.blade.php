@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Performance Dashboard')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Store Overview</h3>
            <p class="text-muted small mb-0">Real-time performance metrics and business activity</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('admin.products.create') }}" class="btn btn-store-primary btn-store-sm">
                <i class="bi bi-plus-lg me-1"></i> New Product
            </a>
            <a href="{{ route('admin.categories.create') }}" class="btn btn-store-outline btn-store-sm">
                <i class="bi bi-tag me-1"></i> New Category
            </a>
        </div>
    </div>
</div>

{{-- Metric Stat Cards --}}
<div class="row g-3 mb-4">
    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-info">
                <h6>Total Revenue</h6>
                <h3>${{ number_format($totalRevenue, 2) }}</h3>
                <span class="small text-success fw-semibold"><i class="bi bi-check-circle-fill me-1"></i> Paid Orders</span>
            </div>
            <div class="stat-icon-box stat-icon-emerald">
                <i class="bi bi-currency-dollar"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-info">
                <h6>Total Orders</h6>
                <h3>{{ number_format($totalOrders) }}</h3>
                <span class="small text-muted">{{ $pendingOrders }} pending review</span>
            </div>
            <div class="stat-icon-box stat-icon-blue">
                <i class="bi bi-receipt"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-info">
                <h6>Catalog Products</h6>
                <h3>{{ number_format($totalProducts) }}</h3>
                <span class="small text-muted">{{ $activeProducts }} live in storefront</span>
            </div>
            <div class="stat-icon-box stat-icon-purple">
                <i class="bi bi-box-seam"></i>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-sm-6">
        <div class="stat-card">
            <div class="stat-card-info">
                <h6>Categories</h6>
                <h3>{{ number_format($totalCategories) }}</h3>
                <span class="small text-muted">Organized hierarchy</span>
            </div>
            <div class="stat-icon-box stat-icon-amber">
                <i class="bi bi-tags"></i>
            </div>
        </div>
    </div>
</div>

{{-- Order Pipeline Status Summary --}}
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="card border rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted fw-semibold">Pending Fulfillment</span>
                <span class="status-pill status-pending">{{ $pendingOrders }}</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-warning" style="width: {{ $totalOrders > 0 ? ($pendingOrders / $totalOrders) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted fw-semibold">In Processing</span>
                <span class="status-pill status-processing">{{ $processingOrders }}</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-info" style="width: {{ $totalOrders > 0 ? ($processingOrders / $totalOrders) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted fw-semibold">Shipped / In Transit</span>
                <span class="status-pill status-shipped">{{ $shippedOrders }}</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-primary" style="width: {{ $totalOrders > 0 ? ($shippedOrders / $totalOrders) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>

    <div class="col-md-3 col-6">
        <div class="card border rounded-3 p-3 bg-white">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="small text-muted fw-semibold">Completed Orders</span>
                <span class="status-pill status-completed">{{ $completedOrders }}</span>
            </div>
            <div class="progress" style="height: 4px;">
                <div class="progress-bar bg-success" style="width: {{ $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0 }}%"></div>
            </div>
        </div>
    </div>
</div>

{{-- Recent Orders Card --}}
<div class="admin-card">
    <div class="admin-card-header">
        <div>
            <h5 class="fw-bold text-dark">Recent Customer Orders</h5>
            <span class="text-muted small">Latest incoming transactions</span>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="btn btn-store-outline btn-store-sm">
            View All Orders <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Fulfillment</th>
                    <th>Payment</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($recentOrders as $order)
                    @php
                        $statusClass = match(strtolower($order->status)) {
                            'completed' => 'status-completed',
                            'processing' => 'status-processing',
                            'shipped' => 'status-shipped',
                            default => 'status-pending'
                        };
                        $paymentClass = match(strtolower($order->payment_status)) {
                            'paid' => 'status-paid',
                            default => 'status-unpaid'
                        };
                    @endphp
                    <tr>
                        <td class="fw-bold text-dark">
                            <a href="{{ route('admin.orders.show', $order) }}" class="text-decoration-none text-dark hover-text-primary">
                                #{{ $order->order_number }}
                            </a>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $order->customer_name }}</div>
                            <div class="text-muted small">{{ $order->customer_email }}</div>
                        </td>
                        <td class="text-muted small">
                            {{ $order->created_at->format('M d, Y • h:i A') }}
                        </td>
                        <td class="fw-bold text-dark">
                            ${{ number_format($order->total, 2) }}
                        </td>
                        <td>
                            <span class="status-pill {{ $statusClass }} text-capitalize">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td>
                            <span class="status-pill {{ $paymentClass }} text-capitalize">
                                {{ $order->payment_status }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-light border" title="View details">
                                <i class="bi bi-eye"></i> Details
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            No orders placed yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
