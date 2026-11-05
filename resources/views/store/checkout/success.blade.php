@extends('layouts.store')

@section('title', 'Order Confirmed #' . $order->order_number . ' | ' . config('app.name', 'SHOPX'))

@section('content')
<div class="container py-5">
    <div class="success-container">
        {{-- Success Checkmark Icon --}}
        <div class="success-icon-badge">
            <i class="bi bi-check-lg"></i>
        </div>

        <div class="d-flex justify-content-center align-items-center gap-2 mb-3 flex-wrap">
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold">
                Payment: {{ $order->payment_method_label }}
            </span>
            @if ($order->payment_status === 'paid')
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                    <i class="bi bi-check-circle-fill me-1"></i> Paid in Full
                </span>
            @else
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-3 py-1 fw-bold">
                    <i class="bi bi-clock-history me-1"></i> Payment Pending
                </span>
            @endif
        </div>

        <h2 class="fw-bold text-dark mb-2">Thank you for your order!</h2>
        <p class="text-muted mb-4">
            We've received your order and have sent a confirmation email to <span class="fw-semibold text-dark">{{ $order->customer_email }}</span>.
        </p>

        {{-- Order Number Box --}}
        <div class="bg-light border rounded-3 p-3 mb-4 d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
            <div>
                <span class="text-muted small d-block">Order Reference Number:</span>
                <span class="fw-bold fs-5 text-dark">#{{ $order->order_number }}</span>
            </div>
            <div class="text-sm-end">
                <span class="text-muted small d-block">Order Date:</span>
                <span class="fw-semibold text-dark">{{ $order->created_at->format('M d, Y') }}</span>
            </div>
        </div>

        {{-- Order Fulfillment Progress Stepper --}}
        <div class="card border rounded-4 text-start mb-4 p-4 bg-white shadow-xs">
            <h6 class="fw-bold mb-3 text-dark d-flex align-items-center gap-2">
                <i class="bi bi-truck text-primary"></i> Shipment Tracking & Status
            </h6>

            @php
                $step = $order->status_step;
            @endphp
            <div class="position-relative my-3">
                <div class="progress" style="height: 4px;">
                    <div class="progress-bar bg-primary" role="progressbar" style="width: {{ $step >= 4 ? 100 : ($step == 3 ? 75 : ($step == 2 ? 40 : 15)) }}%"></div>
                </div>

                <div class="d-flex justify-content-between text-center position-relative" style="margin-top: -14px;">
                    <div>
                        <div class="rounded-circle {{ $step >= 1 ? 'bg-primary text-white' : 'bg-light text-muted border' }} mx-auto d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 11px;">
                            <i class="bi bi-check"></i>
                        </div>
                        <span class="small fw-semibold d-block mt-1 text-dark" style="font-size: 11px;">Placed</span>
                    </div>

                    <div>
                        <div class="rounded-circle {{ $step >= 2 ? 'bg-primary text-white' : 'bg-light text-muted border' }} mx-auto d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 11px;">
                            <i class="bi {{ $step >= 2 ? 'bi-check' : 'bi-box-seam' }}"></i>
                        </div>
                        <span class="small fw-semibold d-block mt-1 {{ $step >= 2 ? 'text-dark' : 'text-muted' }}" style="font-size: 11px;">Processing</span>
                    </div>

                    <div>
                        <div class="rounded-circle {{ $step >= 3 ? 'bg-primary text-white' : 'bg-light text-muted border' }} mx-auto d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 11px;">
                            <i class="bi {{ $step >= 3 ? 'bi-check' : 'bi-truck' }}"></i>
                        </div>
                        <span class="small fw-semibold d-block mt-1 {{ $step >= 3 ? 'text-dark' : 'text-muted' }}" style="font-size: 11px;">Shipped</span>
                    </div>

                    <div>
                        <div class="rounded-circle {{ $step >= 4 ? 'bg-success text-white' : 'bg-light text-muted border' }} mx-auto d-flex align-items-center justify-content-center" style="width: 24px; height: 24px; font-size: 11px;">
                            <i class="bi bi-house-door"></i>
                        </div>
                        <span class="small fw-semibold d-block mt-1 {{ $step >= 4 ? 'text-success fw-bold' : 'text-muted' }}" style="font-size: 11px;">Delivered</span>
                    </div>
                </div>
            </div>

            @if ($order->carrier && $order->tracking_number)
                <div class="mt-3 p-3 bg-light rounded-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border">
                    <div>
                        <span class="badge bg-white text-dark border me-1">{{ $order->carrier }}</span>
                        <span class="text-secondary small">Tracking: <code>{{ $order->tracking_number }}</code></span>
                    </div>
                    @if ($order->live_tracking_url)
                        <a href="{{ $order->live_tracking_url }}" target="_blank" class="btn btn-store-primary btn-store-sm">
                            Track Package <i class="bi bi-box-arrow-up-right ms-1"></i>
                        </a>
                    @endif
                </div>
            @else
                <div class="mt-2 text-muted small">
                    <i class="bi bi-info-circle me-1"></i> Courier tracking information will be updated as soon as your parcel leaves our fulfillment center.
                </div>
            @endif

            @if ($order->estimated_delivery)
                <div class="mt-2 text-secondary small">
                    <i class="bi bi-calendar-event me-1"></i> Estimated Delivery: <strong class="text-dark">{{ $order->estimated_delivery->format('l, F d, Y') }}</strong>
                </div>
            @endif
        </div>

        {{-- Order Breakdown --}}
        <div class="card border rounded-3 text-start mb-4 overflow-hidden shadow-xs">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark">Order Items ({{ $order->items->count() }})</h6>
                <a href="{{ route('store.orders.invoice', $order->order_number) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-printer me-1"></i> View Receipt / Invoice
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="ps-3 py-3" style="width: 60px;">
                                        @if ($item->product && $item->product->image)
                                            <img src="{{ Str::startsWith($item->product->image, ['http://', 'https://']) ? $item->product->image : asset('storage/' . $item->product->image) }}" alt="{{ $item->product->name }}" class="rounded border bg-light p-1" style="width: 48px; height: 48px; object-fit: contain; object-position: center;">
                                        @else
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                                <i class="bi bi-box"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="py-3">
                                        <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                        @if ($item->variant_name)
                                            <div class="text-muted small">{{ $item->variant_name }}</div>
                                        @endif
                                        <div class="text-muted small">Qty: {{ $item->quantity }} × ${{ number_format($item->price, 2) }}</div>
                                    </td>
                                    <td class="text-end pe-3 py-3 fw-bold text-dark">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-light p-3">
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-medium text-dark">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Estimated Shipping:</span>
                    <span class="fw-medium text-success">
                        {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'FREE' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between pt-2 border-top fw-bold fs-6">
                    <span class="text-dark">Total Amount:</span>
                    <span class="text-dark">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Shipping Info Card --}}
        <div class="card border rounded-3 text-start mb-4 p-3 bg-light shadow-xs">
            <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-geo-alt me-1"></i> Delivery Details</h6>
            <div class="small text-secondary">
                <div class="fw-semibold text-dark">{{ $order->customer_name }}</div>
                <div>{{ $order->shipping_address }}</div>
                <div>{{ $order->shipping_city }}, {{ $order->shipping_postcode }}</div>
                <div>{{ $order->shipping_country }}</div>
                <div class="mt-1 text-muted"><i class="bi bi-telephone me-1"></i> {{ $order->customer_phone }}</div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
            <a href="{{ route('store.home') }}" class="btn btn-store-primary">
                <i class="bi bi-arrow-left me-1"></i> Continue Shopping
            </a>
            <a href="{{ route('store.orders.invoice', $order->order_number) }}" target="_blank" class="btn btn-store-outline">
                <i class="bi bi-receipt me-1"></i> Print / Save Invoice
            </a>
            @auth
                <a href="{{ route('store.account') }}" class="btn btn-store-outline">
                    <i class="bi bi-person me-1"></i> View in Account
                </a>
            @endauth
        </div>
    </div>
</div>
@endsection
