@extends('layouts.store')

@section('title', 'Secure Checkout | ' . config('app.name', 'SHOPX'))

@section('content')
<div class="container py-5">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small text-muted">
            <li class="breadcrumb-item"><a href="{{ route('store.home') }}" class="text-secondary">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ route('store.cart.index') }}" class="text-secondary">Cart</a></li>
            <li class="breadcrumb-item active" aria-current="page">Checkout</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom">
        <div>
            <h2 class="fw-bold text-dark mb-1">Express Checkout</h2>
            <p class="text-muted small mb-0">Complete your shipping information to finalize your purchase</p>
        </div>
        <a href="{{ route('store.cart.index') }}" class="btn btn-store-outline btn-store-sm">
            <i class="bi bi-arrow-left me-1"></i> Return to Cart
        </a>
    </div>

    <form method="POST" action="{{ route('store.checkout.store') }}" id="checkoutForm">
        @csrf

        <div class="row g-4 align-items-start">
            {{-- Customer & Shipping Form --}}
            <div class="col-lg-7">
                {{-- Step 1: Contact Details --}}
                <div class="checkout-card mb-4">
                    <div class="checkout-card-header d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">1</span>
                        <h5>Contact Information</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="customer_name" class="form-label small fw-semibold text-secondary">Full Name *</label>
                                <input
                                    type="text"
                                    id="customer_name"
                                    name="customer_name"
                                    class="form-control @error('customer_name') is-invalid @enderror"
                                    value="{{ old('customer_name', auth()->user()->name ?? '') }}"
                                    placeholder="Jane Doe"
                                    required
                                >
                                @error('customer_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="customer_email" class="form-label small fw-semibold text-secondary">Email Address *</label>
                                <input
                                    type="email"
                                    id="customer_email"
                                    name="customer_email"
                                    class="form-control @error('customer_email') is-invalid @enderror"
                                    value="{{ old('customer_email', auth()->user()->email ?? '') }}"
                                    placeholder="jane@example.com"
                                    required
                                >
                                @error('customer_email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <label for="customer_phone" class="form-label small fw-semibold text-secondary">Phone Number *</label>
                                <input
                                    type="tel"
                                    id="customer_phone"
                                    name="customer_phone"
                                    class="form-control @error('customer_phone') is-invalid @enderror"
                                    value="{{ old('customer_phone') }}"
                                    placeholder="+1 (555) 000-0000"
                                    required
                                >
                                @error('customer_phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 2: Delivery Address --}}
                <div class="checkout-card mb-4">
                    <div class="checkout-card-header d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">2</span>
                        <h5>Shipping Address</h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="shipping_address" class="form-label small fw-semibold text-secondary">Street Address *</label>
                                <input
                                    type="text"
                                    id="shipping_address"
                                    name="shipping_address"
                                    class="form-control @error('shipping_address') is-invalid @enderror"
                                    value="{{ old('shipping_address') }}"
                                    placeholder="123 Luxury Ave, Apt 4B"
                                    required
                                >
                                @error('shipping_address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="shipping_city" class="form-label small fw-semibold text-secondary">City *</label>
                                <input
                                    type="text"
                                    id="shipping_city"
                                    name="shipping_city"
                                    class="form-control @error('shipping_city') is-invalid @enderror"
                                    value="{{ old('shipping_city') }}"
                                    placeholder="New York"
                                    required
                                >
                                @error('shipping_city')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="shipping_postcode" class="form-label small fw-semibold text-secondary">Postal Code *</label>
                                <input
                                    type="text"
                                    id="shipping_postcode"
                                    name="shipping_postcode"
                                    class="form-control @error('shipping_postcode') is-invalid @enderror"
                                    value="{{ old('shipping_postcode') }}"
                                    placeholder="10001"
                                    required
                                >
                                @error('shipping_postcode')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-4">
                                <label for="shipping_country" class="form-label small fw-semibold text-secondary">Country *</label>
                                <input
                                    type="text"
                                    id="shipping_country"
                                    name="shipping_country"
                                    class="form-control @error('shipping_country') is-invalid @enderror"
                                    value="{{ old('shipping_country', 'United States') }}"
                                    required
                                >
                                @error('shipping_country')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-12">
                                <label for="notes" class="form-label small fw-semibold text-secondary">Order Notes (Optional)</label>
                                <textarea
                                    id="notes"
                                    name="notes"
                                    class="form-control @error('notes') is-invalid @enderror"
                                    rows="2"
                                    placeholder="Special delivery instructions or gate access code..."
                                >{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Step 3: Payment Method Selection --}}
                <div class="checkout-card mb-4">
                    <div class="checkout-card-header d-flex align-items-center gap-2">
                        <span class="badge bg-primary rounded-circle" style="width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center;">3</span>
                        <h5>Payment Method</h5>
                    </div>
                    <div class="card-body p-4">
                        {{-- Hidden Stripe Payment Intent ID --}}
                        <input type="hidden" name="stripe_payment_intent_id" id="stripe_payment_intent_id">

                        {{-- Option 1: Credit / Debit Card (Stripe) --}}
                        <div class="border rounded-3 p-3 mb-3 bg-white payment-option-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_card" value="card" {{ old('payment_method') === 'card' ? 'checked' : '' }} onchange="togglePaymentFields(this.value)">
                                    <label class="form-check-label fw-bold text-dark" for="pay_card">
                                        Credit / Debit Card
                                    </label>
                                </div>
                                <div class="d-flex gap-1 text-muted fs-5 align-items-center">
                                    <span class="badge bg-light text-dark border px-2 py-1 small fw-bold me-1">Stripe</span>
                                    <i class="bi bi-credit-card-2-front"></i>
                                    <i class="bi bi-shield-check text-success"></i>
                                </div>
                            </div>

                            <div id="cardFields" class="mt-3 pt-3 border-top {{ old('payment_method') === 'card' ? '' : 'd-none' }}">
                                @if (! empty($stripeConfigured))
                                    <div class="mb-3">
                                        <label class="form-label small text-secondary fw-semibold mb-1">
                                            Card Information
                                        </label>
                                        <div id="stripe-card-element" class="form-control p-3 bg-white" style="min-height: 44px;"></div>
                                        <div id="stripe-card-errors" class="text-danger small mt-2 d-none"></div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between text-muted small">
                                        <span class="d-flex align-items-center gap-1">
                                            <i class="bi bi-shield-lock-fill text-success"></i>
                                            <span>256-bit SSL encrypted checkout</span>
                                        </span>
                                        <span class="fw-semibold text-primary">Powered by Stripe</span>
                                    </div>
                                @else
                                    <div class="alert alert-light border py-2 px-3 mb-3 small">
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="bi bi-info-circle-fill text-primary fs-5 mt-1"></i>
                                            <div>
                                                <strong class="text-dark">Stripe Test Mode (Simulation Active)</strong>
                                                <div class="text-muted">Test keys are set to placeholder in <code>.env</code>. You can test with test cards or click Place Order directly.</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col-12">
                                            <label class="form-label small text-secondary fw-semibold mb-1">Name on Card</label>
                                            <input type="text" name="card_holder" class="form-control form-control-sm" placeholder="Jane Doe" value="{{ old('card_holder') }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label small text-secondary fw-semibold mb-1">Card Number</label>
                                            <div class="input-group input-group-sm">
                                                <span class="input-group-text bg-white"><i class="bi bi-credit-card text-muted"></i></span>
                                                <input type="text" name="card_number" class="form-control" placeholder="4242 •••• •••• 4242" maxlength="19" value="{{ old('card_number') }}">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-secondary fw-semibold mb-1">Expiry (MM/YY)</label>
                                            <input type="text" name="card_expiry" class="form-control form-control-sm" placeholder="12/28" maxlength="5" value="{{ old('card_expiry') }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small text-secondary fw-semibold mb-1">CVC / CVV</label>
                                            <input type="password" name="card_cvc" class="form-control form-control-sm" placeholder="•••" maxlength="4" value="{{ old('card_cvc') }}">
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center gap-2 mt-2 text-muted small">
                                        <i class="bi bi-lock-fill text-success"></i>
                                        <span>Encrypted 256-bit SSL transaction</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Option 2: Cash on Delivery --}}
                        <div class="border rounded-3 p-3 mb-3 bg-white payment-option-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod" {{ old('payment_method', 'cod') === 'cod' ? 'checked' : '' }} onchange="togglePaymentFields(this.value)">
                                    <label class="form-check-label fw-bold text-dark" for="pay_cod">
                                        Cash on Delivery (COD)
                                    </label>
                                </div>
                                <span class="badge bg-light text-secondary border">Standard</span>
                            </div>
                            <p class="text-muted small ms-4 mb-0 mt-1">
                                Pay upon receipt of goods at your delivery doorstep.
                            </p>
                        </div>

                        {{-- Option 3: Direct Bank Transfer --}}
                        <div class="border rounded-3 p-3 bg-white payment-option-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="payment_method" id="pay_bank" value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }} onchange="togglePaymentFields(this.value)">
                                    <label class="form-check-label fw-bold text-dark" for="pay_bank">
                                        Direct Wire / Bank Transfer
                                    </label>
                                </div>
                                <span class="badge bg-light text-secondary border">Electronic</span>
                            </div>
                            <p class="text-muted small ms-4 mb-0 mt-1">
                                Wire payment directly to our corporate bank account.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right Column: Order Summary --}}
            <div class="col-lg-5">
                <div class="cart-summary-box">
                    <h5 class="fw-bold text-dark mb-3">Order Summary</h5>

                    {{-- Mini item list --}}
                    <div class="border-bottom pb-3 mb-3">
                        @foreach ($items as $item)
                            @php
                                $product = $item['product'];
                                $variant = $item['variant'];
                                $quantity = $item['quantity'];
                                $subtotal = $item['subtotal'];
                            @endphp
                            <div class="d-flex align-items-center gap-3 py-2">
                                @if ($product->image)
                                    <img src="{{ Str::startsWith($product->image, ['http://', 'https://']) ? $product->image : asset('storage/' . $product->image) }}" alt="{{ $product->name }}" class="rounded" style="width: 48px; height: 48px; object-fit: cover;">
                                @else
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center text-muted" style="width: 48px; height: 48px;">
                                        <i class="bi bi-box"></i>
                                    </div>
                                @endif
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="fw-bold text-dark text-truncate small">{{ $product->name }}</div>
                                    @if ($variant)
                                        <div class="text-muted small">{{ $variant->display_name }}</div>
                                    @endif
                                    <div class="text-muted small">Qty: {{ $quantity }} × ${{ number_format($item['unit_price'], 2) }}</div>
                                </div>
                                <div class="fw-semibold text-dark small text-end">
                                    ${{ number_format($subtotal, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="summary-row">
                        <span>Items Subtotal</span>
                        <span class="fw-semibold text-dark">${{ number_format($total, 2) }}</span>
                    </div>

                    <div class="summary-row">
                        <span>Shipping Cost</span>
                        @if ($total >= 75)
                            <span class="fw-bold text-success">FREE</span>
                        @else
                            <span class="fw-semibold text-dark">$10.00</span>
                        @endif
                    </div>

                    @php
                        $grandTotal = $total >= 75 ? $total : $total + 10;
                    @endphp

                    <div class="summary-row total-row">
                        <span>Total Due</span>
                        <span class="text-primary">${{ number_format($grandTotal, 2) }}</span>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-store-primary w-100 py-3 fw-bold fs-6">
                            <i class="bi bi-lock-fill me-2"></i> Place Order Now
                        </button>
                    </div>

                    <div class="mt-4 pt-3 border-top text-center">
                        <div class="d-flex justify-content-center align-items-center gap-3 text-muted small">
                            <span><i class="bi bi-shield-check text-success"></i> 256-Bit SSL</span>
                            <span><i class="bi bi-truck text-primary"></i> Tracked Delivery</span>
                            <span><i class="bi bi-arrow-repeat text-info"></i> Easy Returns</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@push('scripts')
@if (! empty($stripeConfigured) && ! empty($stripePublishableKey))
<script src="https://js.stripe.com/v3/"></script>
@endif

<script>
    let stripeInstance = null;
    let cardElement = null;
    let stripeClientSecret = null;
    let isStripeInitialized = false;

    async function initStripeElements() {
        @if (! empty($stripeConfigured) && ! empty($stripePublishableKey))
            if (isStripeInitialized) return;

            const cardMount = document.getElementById('stripe-card-element');
            if (!cardMount) return;

            try {
                stripeInstance = Stripe('{{ $stripePublishableKey }}');
                const elements = stripeInstance.elements();

                cardElement = elements.create('card', {
                    style: {
                        base: {
                            color: '#0f172a',
                            fontFamily: "'Plus Jakarta Sans', sans-serif",
                            fontSize: '14.5px',
                            '::placeholder': { color: '#94a3b8' }
                        },
                        invalid: {
                            color: '#ef4444',
                            iconColor: '#ef4444'
                        }
                    }
                });

                cardElement.mount('#stripe-card-element');

                cardElement.on('change', function(event) {
                    const displayError = document.getElementById('stripe-card-errors');
                    if (!displayError) return;
                    if (event.error) {
                        displayError.textContent = event.error.message;
                        displayError.classList.remove('d-none');
                    } else {
                        displayError.textContent = '';
                        displayError.classList.add('d-none');
                    }
                });

                // Request payment intent client secret from server
                const response = await fetch('{{ route('store.checkout.stripe-intent') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });

                const data = await response.json();
                if (response.ok && data.clientSecret) {
                    stripeClientSecret = data.clientSecret;
                    isStripeInitialized = true;
                } else {
                    console.warn('Could not initialize Stripe intent:', data.message);
                }
            } catch (err) {
                console.error('Stripe initialization failed:', err);
            }
        @endif
    }

    function togglePaymentFields(method) {
        const cardFields = document.getElementById('cardFields');
        if (cardFields) {
            if (method === 'card') {
                cardFields.classList.remove('d-none');
                initStripeElements();
            } else {
                cardFields.classList.add('d-none');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const checkedPayment = document.querySelector('input[name="payment_method"]:checked');
        if (checkedPayment && checkedPayment.value === 'card') {
            togglePaymentFields('card');
        }

        const form = document.getElementById('checkoutForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked')?.value;
            if (selectedMethod !== 'card') {
                return; // Let standard form submission process for COD and wire transfer
            }

            @if (! empty($stripeConfigured) && ! empty($stripePublishableKey))
                e.preventDefault();

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnHtml = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Processing Payment...';

                try {
                    if (!stripeInstance || !cardElement || !stripeClientSecret) {
                        await initStripeElements();
                    }

                    const nameInput = document.getElementById('customer_name');
                    const emailInput = document.getElementById('customer_email');

                    const result = await stripeInstance.confirmCardPayment(stripeClientSecret, {
                        payment_method: {
                            card: cardElement,
                            billing_details: {
                                name: nameInput ? nameInput.value : '',
                                email: emailInput ? emailInput.value : ''
                            }
                        }
                    });

                    if (result.error) {
                        const displayError = document.getElementById('stripe-card-errors');
                        if (displayError) {
                            displayError.textContent = result.error.message;
                            displayError.classList.remove('d-none');
                        }
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnHtml;
                    } else if (result.paymentIntent && (result.paymentIntent.status === 'succeeded' || result.paymentIntent.status === 'processing')) {
                        document.getElementById('stripe_payment_intent_id').value = result.paymentIntent.id;
                        form.submit();
                    }
                } catch (err) {
                    console.error('Stripe submit error:', err);
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnHtml;
                }
            @else
                // In test simulation mode, auto-generate a simulated payment intent id
                const intentInput = document.getElementById('stripe_payment_intent_id');
                if (intentInput && !intentInput.value) {
                    intentInput.value = 'pi_test_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                }
            @endif
        });
    });
</script>
@endpush
@endsection
