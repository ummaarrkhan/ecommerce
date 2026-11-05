@extends('layouts.admin')

@section('title', 'Order #' . $order->order_number)
@section('page_title', 'Order Details')

@section('content')
<div class="mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
        <div>
            <a href="{{ route('admin.orders.index') }}" class="small text-secondary text-decoration-none">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
            <div class="d-flex align-items-center gap-3 mt-2">
                <h3 class="fw-bold text-dark mb-0">Order #{{ $order->order_number }}</h3>
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
                <span class="status-pill {{ $statusClass }} text-capitalize">{{ $order->status }}</span>
                <span class="status-pill {{ $paymentClass }} text-capitalize">{{ $order->payment_status }}</span>
            </div>
            <p class="text-muted small mt-1 mb-0">Placed on {{ $order->created_at->format('l, F d, Y • h:i A') }}</p>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.invoice', $order) }}" target="_blank" class="btn btn-dark btn-store-sm">
                <i class="bi bi-printer me-1"></i> Print / Save Invoice
            </a>
            <a href="{{ route('store.checkout.success', $order->order_number) }}" target="_blank" class="btn btn-store-outline btn-store-sm">
                <i class="bi bi-eye me-1"></i> Customer View
            </a>
        </div>
    </div>
</div>

<div class="row g-4">
    {{-- Left Column: Line Items --}}
    <div class="col-lg-8">
        {{-- Items Card --}}
        <div class="admin-card mb-4">
            <div class="admin-card-header">
                <h5 class="fw-bold text-dark">Ordered Items ({{ $order->items->count() }})</h5>
            </div>

            <div class="table-responsive">
                <table class="table admin-table align-middle">
                    <thead>
                        <tr>
                            <th style="width: 60px;">Item</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($order->items as $item)
                            <tr>
                                <td>
                                    @if ($item->product && $item->product->image)
                                        <img src="{{ Str::startsWith($item->product->image, ['http://', 'https://']) ? $item->product->image : asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="rounded border" style="width: 44px; height: 44px; object-fit: cover;">
                                    @else
                                        <div class="bg-light rounded border d-flex align-items-center justify-content-center text-muted" style="width: 44px; height: 44px;">
                                            <i class="bi bi-box"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="fw-bold text-dark">{{ $item->product_name ?? 'Product #' . $item->product_id }}</div>
                                    @if ($item->variant_name)
                                        <div class="small text-muted">{{ $item->variant_name }}</div>
                                    @endif
                                    @if ($item->sku)
                                        <div class="text-muted small">SKU: <code>{{ $item->sku }}</code></div>
                                    @endif
                                </td>
                                <td>
                                    ${{ number_format($item->price, 2) }}
                                </td>
                                <td>
                                    <span class="badge bg-light border text-dark">{{ $item->quantity }}</span>
                                </td>
                                <td class="text-end fw-bold text-dark">
                                    ${{ number_format($item->subtotal ?? ($item->price * $item->quantity), 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="p-3 bg-light border-top">
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-medium text-dark">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Shipping Fee:</span>
                    <span class="fw-medium text-dark">${{ number_format($order->shipping_cost, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top fs-6 fw-bold">
                    <span class="text-dark">Total Amount:</span>
                    <span class="text-dark">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Notes / Instructions Card --}}
        @if ($order->notes)
            <div class="card border rounded-3 p-3 bg-white mb-4 shadow-xs">
                <h6 class="fw-bold text-dark mb-1"><i class="bi bi-chat-left-text me-1"></i> Customer Special Instructions</h6>
                <p class="text-muted small mb-0">{{ $order->notes }}</p>
            </div>
        @endif
    </div>

    {{-- Right Column: Status Controls & Fulfillment Management --}}
    <div class="col-lg-4">
        {{-- Fulfillment & Shipment Card --}}
        <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-truck text-primary me-1"></i> Shipment & Courier Fulfillment</h5>

            <form method="POST" action="{{ route('admin.orders.updateFulfillment', $order) }}">
                @csrf
                @method('PATCH')

                <div class="mb-3">
                    <label for="carrier" class="form-label small fw-semibold text-secondary">Delivery Carrier</label>
                    <input type="text" list="carrierList" name="carrier" id="carrier" class="form-control form-control-sm" value="{{ old('carrier', $order->carrier) }}" placeholder="e.g. FedEx, DHL, UPS, USPS">
                    <datalist id="carrierList">
                        <option value="FedEx">
                        <option value="DHL Express">
                        <option value="UPS">
                        <option value="USPS">
                        <option value="Standard Express">
                    </datalist>
                </div>

                <div class="mb-3">
                    <label for="tracking_number" class="form-label small fw-semibold text-secondary">Tracking Number</label>
                    <input type="text" name="tracking_number" id="tracking_number" class="form-control form-control-sm" value="{{ old('tracking_number', $order->tracking_number) }}" placeholder="e.g. 9400100000000000000000">
                </div>

                <div class="mb-3">
                    <label for="estimated_delivery" class="form-label small fw-semibold text-secondary">Estimated Delivery Date</label>
                    <input type="date" name="estimated_delivery" id="estimated_delivery" class="form-control form-control-sm" value="{{ old('estimated_delivery', $order->estimated_delivery ? $order->estimated_delivery->format('Y-m-d') : '') }}">
                </div>

                @if ($order->live_tracking_url)
                    <div class="mb-3">
                        <a href="{{ $order->live_tracking_url }}" target="_blank" class="small text-primary text-decoration-none">
                            <i class="bi bi-box-arrow-up-right me-1"></i> Verify on Carrier Website
                        </a>
                    </div>
                @endif

                <div class="d-flex flex-column gap-2 pt-2 border-top">
                    <button type="submit" name="fulfillment_action" value="save" class="btn btn-sm btn-store-outline">
                        Save Tracking Info
                    </button>

                    @if ($order->status !== 'shipped' && $order->status !== 'completed')
                        <button type="submit" name="fulfillment_action" value="ship" class="btn btn-sm btn-store-primary">
                            <i class="bi bi-send me-1"></i> Dispatch & Mark Shipped
                        </button>
                    @endif

                    @if ($order->status !== 'completed')
                        <button type="submit" name="fulfillment_action" value="deliver" class="btn btn-sm btn-success text-white">
                            <i class="bi bi-check2-circle me-1"></i> Confirm Delivered
                        </button>
                    @endif
                </div>
            </form>
        </div>

        {{-- Payment & Status Controls --}}
        <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-credit-card text-success me-1"></i> Payment & Order State</h5>

            <div class="mb-3 p-2 bg-light rounded small">
                <div><span class="text-muted">Method:</span> <strong class="text-dark">{{ $order->payment_method_label }}</strong></div>
                @if ($order->payment_reference)
                    <div class="text-truncate"><span class="text-muted">Ref:</span> <code>{{ $order->payment_reference }}</code></div>
                @endif
            </div>

            {{-- Update Payment Status --}}
            <form method="POST" action="{{ route('admin.orders.updatePaymentStatus', $order) }}" class="mb-3">
                @csrf
                @method('PATCH')
                <label for="payment_status" class="form-label small fw-semibold text-secondary">Payment Status</label>
                <div class="input-group input-group-sm">
                    <select name="payment_status" id="payment_status" class="form-select">
                        <option value="unpaid" {{ $order->payment_status == 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ $order->payment_status == 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                    <button type="submit" class="btn btn-store-primary">Save</button>
                </div>
            </form>

            {{-- Update Fulfillment Status --}}
            <form method="POST" action="{{ route('admin.orders.updateStatus', $order) }}">
                @csrf
                @method('PATCH')
                <label for="status" class="form-label small fw-semibold text-secondary">Order Lifecycle Status</label>
                <div class="input-group input-group-sm">
                    <select name="status" id="status" class="form-select">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="completed" {{ $order->status == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-store-primary">Save</button>
                </div>
            </form>
        </div>

        {{-- Customer Details Card --}}
        <div class="card border rounded-3 p-4 mb-4 bg-white shadow-xs">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-person me-1"></i> Customer Profile</h5>
            <div class="small">
                <div class="fw-bold text-dark fs-6">{{ $order->customer_name }}</div>
                <div class="text-muted"><i class="bi bi-envelope me-1"></i> {{ $order->customer_email }}</div>
                <div class="text-muted"><i class="bi bi-telephone me-1"></i> {{ $order->customer_phone }}</div>
                @if ($order->user)
                    <div class="mt-2 pt-2 border-top">
                        <span class="badge bg-light text-dark border">Registered Customer #{{ $order->user_id }}</span>
                    </div>
                @else
                    <div class="mt-2 pt-2 border-top">
                        <span class="badge bg-secondary-subtle text-secondary">Guest Checkout</span>
                    </div>
                @endif
            </div>
        </div>

        {{-- Shipping Destination Card --}}
        <div class="card border rounded-3 p-4 bg-white shadow-xs">
            <h5 class="fw-bold text-dark mb-3"><i class="bi bi-geo-alt me-1"></i> Delivery Address</h5>
            <div class="small text-secondary">
                <div class="fw-semibold text-dark">{{ $order->shipping_address }}</div>
                <div>{{ $order->shipping_city }}, {{ $order->shipping_postcode }}</div>
                <div class="fw-medium text-dark">{{ $order->shipping_country }}</div>
            </div>
        </div>
    </div>
</div>
@endsection
