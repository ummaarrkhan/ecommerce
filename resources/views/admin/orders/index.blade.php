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
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-white border text-dark px-3 py-2 rounded-pill small">
                {{ $orders->total() }} Total Orders
            </span>
            <button type="button" class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteByStatusModal">
                <i class="bi bi-trash3 me-1"></i> Delete by Status
            </button>
        </div>
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
                            <div class="d-inline-flex align-items-center gap-1">
                                <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-light border" title="Manage Order">
                                    <i class="bi bi-pencil-square me-1"></i> Manage
                                </a>
                                @php
                                    $isDeletable = in_array(strtolower($order->status), ['cancelled', 'completed', 'pending']);
                                @endphp
                                @if ($isDeletable)
                                    <form method="POST" action="{{ route('admin.orders.destroy', $order) }}" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete order #{{ $order->order_number }} (Status: {{ ucfirst($order->status) }})?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger border" title="Delete Order ({{ ucfirst($order->status) }})">
                                            <i class="bi bi-trash3"></i>
                                        </button>
                                    </form>
                                @else
                                    <button type="button" class="btn btn-sm btn-light border text-muted opacity-50" title="Cannot delete order while status is '{{ ucfirst($order->status) }}'. Cancel order first." disabled>
                                        <i class="bi bi-trash3"></i>
                                    </button>
                                @endif
                            </div>
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

{{-- Modal: Delete Orders by Status --}}
<div class="modal fade" id="deleteByStatusModal" tabindex="-1" aria-labelledby="deleteByStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="{{ route('admin.orders.destroyByStatus') }}">
                @csrf
                @method('DELETE')
                <div class="modal-header border-bottom">
                    <h5 class="modal-title fw-bold text-danger d-flex align-items-center gap-2" id="deleteByStatusModalLabel">
                        <i class="bi bi-exclamation-triangle-fill"></i> Delete Orders by Status
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Select a status to permanently remove all matching orders and their associated items from the database.
                    </p>

                    <div class="mb-3">
                        <label for="bulk_delete_status" class="form-label small fw-semibold text-secondary">Select Order Status to Delete</label>
                        <select name="status" id="bulk_delete_status" class="form-select" required>
                            <option value="" disabled selected>-- Choose Status --</option>
                            <option value="cancelled">Cancelled Orders</option>
                            <option value="completed">Completed Orders</option>
                            <option value="pending">Pending Orders</option>
                        </select>
                        <div class="form-text text-muted small mt-1">
                            Active fulfillment orders (<em>Processing</em> and <em>Shipped</em>) are protected and cannot be deleted.
                        </div>
                    </div>

                    <div class="form-check p-3 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" id="confirm_bulk_delete" required>
                        <label class="form-check-label small fw-semibold text-dark" for="confirm_bulk_delete">
                            I understand that deleting orders is permanent and cannot be undone.
                        </label>
                    </div>
                </div>
                <div class="modal-footer border-top bg-light">
                    <button type="button" class="btn btn-sm btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-danger">
                        <i class="bi bi-trash3 me-1"></i> Delete Matching Orders
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
