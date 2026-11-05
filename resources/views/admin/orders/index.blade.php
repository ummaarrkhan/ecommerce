@extends('layouts.admin')

@section('title', 'Orders Management')
@section('page_title', 'Customer Orders')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-1">Orders</h3>
            <p class="text-muted small mb-0">Track order fulfillment, payment verification, and customer delivery</p>
        </div>
        <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
            {{ $orders->total() }} Total Orders
        </span>
    </div>
</div>

{{-- Filters Card --}}
<div class="card border rounded-3 p-3 mb-4 bg-white shadow-xs">
    <form method="GET" action="{{ route('admin.orders.index') }}" class="row g-2 align-items-center">
        <div class="col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light text-muted"><i class="bi bi-search"></i></span>
                <input
                    type="text"
                    name="search"
                    class="form-control"
                    placeholder="Search by order #, customer, email..."
                    value="{{ request('search') }}"
                >
            </div>
        </div>

        <div class="col-md-3">
            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Fulfillment Statuses</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>

        <div class="col-md-3">
            <select name="payment_status" class="form-select form-select-sm" onchange="this.form.submit()">
                <option value="">All Payment Statuses</option>
                <option value="unpaid" {{ request('payment_status') == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                <option value="paid" {{ request('payment_status') == 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="refunded" {{ request('payment_status') == 'refunded' ? 'selected' : '' }}>Refunded</option>
            </select>
        </div>

        <div class="col-md-2 text-md-end">
            @if (request()->hasAny(['search', 'status', 'payment_status']))
                <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-store-outline w-100">
                    <i class="bi bi-x-circle me-1"></i> Reset
                </a>
            @else
                <button type="submit" class="btn btn-sm btn-store-primary w-100">
                    Filter
                </button>
            @endif
        </div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h5 class="fw-bold text-dark">Order Transactions</h5>
        <span class="text-muted small">Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
    </div>

    <div class="table-responsive">
        <table class="table admin-table align-middle">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer Name</th>
                    <th>Date</th>
                    <th>Destination</th>
                    <th>Total</th>
                    <th>Fulfillment</th>
                    <th>Payment</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $statusClass = match(strtolower($order->status)) {
                            'completed' => 'status-completed',
                            'processing' => 'status-processing',
                            'shipped' => 'status-shipped',
                            'cancelled' => 'status-cancelled',
                            default => 'status-pending'
                        };
                        $paymentClass = match(strtolower($order->payment_status)) {
                            'paid' => 'status-paid',
                            'refunded' => 'status-shipped',
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
                        <td class="small text-secondary">
                            {{ $order->shipping_city }}, {{ $order->shipping_country }}
                        </td>
                        <td class="fw-bold text-dark fs-6">
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
                            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-light border" title="Manage Order">
                                <i class="bi bi-pencil-square me-1"></i> Manage
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 mb-2 d-block opacity-50"></i>
                            <h6 class="fw-bold text-dark">No orders found</h6>
                            <p class="small text-muted mb-0">Try clearing your filters or wait for new store checkouts.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($orders->hasPages())
        <div class="p-3 border-top d-flex justify-content-center">
            {{ $orders->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>
@endsection
