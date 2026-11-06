@extends('layouts.store')

@section('title', 'My Account | ' . config('app.name', 'MONO'))

@section('content')
<div class="container py-5">
    {{-- Page Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-3 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">My Account</h2>
            <p class="text-muted small mb-0">Manage your profile, view purchase history and tracking details</p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('store.home') }}" class="btn btn-store-outline btn-store-sm">
                <i class="bi bi-arrow-left me-1"></i> Continue Shopping
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-store-sm">
                    <i class="bi bi-box-arrow-right me-1"></i> Sign Out
                </button>
            </form>
        </div>
    </div>

    <div class="row g-4">
        {{-- Profile Sidebar Card --}}
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-body p-4 text-center border-bottom bg-white">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold fs-3 mb-3 shadow-sm" style="width: 72px; height: 72px;">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    <h5 class="fw-bold mb-1 text-dark">{{ $user->name }}</h5>
                    <p class="text-muted small mb-2">{{ $user->email }}</p>
                    <span class="badge bg-light text-secondary border px-3 py-1 rounded-pill small">
                        Member since {{ $user->created_at ? $user->created_at->format('M Y') : 'Recently' }}
                    </span>
                </div>

                <div class="card-body p-4 bg-light">
                    <h6 class="fw-bold text-dark small text-uppercase mb-3">Quick Overview</h6>
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted">Total Orders Placed</span>
                        <span class="fw-bold text-dark">{{ $orders->count() }}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom small">
                        <span class="text-muted">Active Role</span>
                        <span class="fw-bold text-dark">{{ $user->roles->pluck('name')->first() ?? 'Customer' }}</span>
                    </div>
                    @hasanyrole('super-admin|admin')
                        <div class="mt-3">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-store-accent btn-store-sm w-100">
                                <i class="bi bi-speedometer2 me-1"></i> Go to Admin Dashboard
                            </a>
                        </div>
                    @endhasanyrole
                </div>
            </div>

            {{-- Support & Perks Box --}}
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h6 class="fw-bold mb-2 text-dark"><i class="bi bi-shield-check text-success me-1"></i> Dedicated Support</h6>
                <p class="text-muted small mb-3">Have questions about your order or need to request a return?</p>
                <a href="mailto:support@example.com" class="btn btn-store-outline btn-store-sm w-100">
                    <i class="bi bi-envelope me-1"></i> Contact Concierge
                </a>
            </div>
        </div>

        {{-- Orders List --}}
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-dark">Order History</h5>
                    <span class="badge bg-secondary rounded-pill">{{ $orders->count() }} {{ Str::plural('order', $orders->count()) }}</span>
                </div>

                <div class="card-body p-0">
                    @if ($orders->isEmpty())
                        <div class="text-center py-5 px-3">
                            <div class="text-muted mb-3">
                                <i class="bi bi-bag-x fs-1 opacity-50"></i>
                            </div>
                            <h5 class="fw-bold text-dark mb-1">No orders placed yet</h5>
                            <p class="text-muted small mb-4">When you place an order, all details and shipment tracking will appear here.</p>
                            <a href="{{ route('store.home') }}#featured-products" class="btn btn-store-primary">
                                Explore Products & Shop Now
                            </a>
                        </div>
                    @else
                        <div class="list-group list-group-flush">
                            @foreach ($orders as $order)
                                <div class="list-group-item p-4">
                                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-2 mb-3">
                                        <div>
                                            <span class="fw-bold text-dark me-2">#{{ $order->order_number }}</span>
                                            <span class="text-muted small">{{ $order->created_at->format('M d, Y • h:i A') }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
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
                                            <span class="status-pill {{ $statusClass }} text-capitalize">
                                                {{ $order->status }}
                                            </span>
                                            <span class="status-pill {{ $paymentClass }} text-capitalize">
                                                {{ $order->payment_status }}
                                            </span>
                                        </div>
                                    </div>

                                    {{-- Order Items Preview --}}
                                    <div class="bg-light rounded-3 p-3 mb-3">
                                        @foreach ($order->items as $item)
                                            <div class="d-flex align-items-center justify-content-between py-1 small">
                                                <div class="d-flex align-items-center gap-2">
                                                    <span class="badge bg-white border text-dark">{{ $item->quantity }}x</span>
                                                    <span class="fw-semibold text-dark">{{ $item->product->name ?? 'Product #' . $item->product_id }}</span>
                                                </div>
                                                <span class="text-muted">${{ number_format($item->subtotal ?? ($item->price * $item->quantity), 2) }}</span>
                                            </div>
                                            @if ($item->variant_name)
                                                <div class="small text-muted ps-5">{{ $item->variant_name }}</div>
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Shipment Tracking Box (if assigned) --}}
                                    @if ($order->carrier && $order->tracking_number)
                                        <div class="p-2 mb-3 bg-light rounded-3 d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-2 border">
                                            <div class="small">
                                                <i class="bi bi-truck text-primary me-1"></i>
                                                <strong class="text-dark">{{ $order->carrier }}</strong>:
                                                <code>{{ $order->tracking_number }}</code>
                                                @if ($order->estimated_delivery)
                                                    <span class="text-muted ms-2">• Est: {{ $order->estimated_delivery->format('M d') }}</span>
                                                @endif
                                            </div>
                                            @if ($order->live_tracking_url)
                                                <a href="{{ $order->live_tracking_url }}" target="_blank" class="btn btn-sm btn-store-primary py-0 px-2" style="font-size: 11px;">
                                                    Track <i class="bi bi-box-arrow-up-right ms-1"></i>
                                                </a>
                                            @endif
                                        </div>
                                    @endif

                                    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center pt-2 border-top gap-2">
                                        <div class="small text-muted">
                                            <span class="badge bg-light text-secondary border me-2">{{ $order->payment_method_label }}</span>
                                            <i class="bi bi-geo-alt me-1"></i> Destination: <span class="text-dark fw-medium">{{ $order->shipping_city }}, {{ $order->shipping_country }}</span>
                                        </div>
                                        <div class="d-flex align-items-center gap-3 justify-content-between justify-content-sm-end">
                                            <a href="{{ route('store.orders.invoice', $order->order_number) }}" target="_blank" class="btn btn-sm btn-light border py-1 px-2" style="font-size: 12px;">
                                                <i class="bi bi-printer me-1"></i> Invoice
                                            </a>
                                            <div>
                                                <span class="small text-muted me-1">Total:</span>
                                                <span class="fw-bold text-dark fs-6">${{ number_format($order->total, 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
