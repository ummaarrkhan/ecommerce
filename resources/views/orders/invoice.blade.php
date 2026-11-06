<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->order_number }} | {{ config('app.name', 'MONO') }}</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #0f172a;
            background-color: #f8fafc;
            padding: 30px 15px;
        }

        .invoice-card {
            max-width: 860px;
            margin: 0 auto;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.05);
            padding: 40px;
        }

        .brand-badge {
            background: #0f172a;
            color: #fff;
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
        }

        .invoice-table th {
            background-color: #f8fafc;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            padding: 12px 16px;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
        }

        .invoice-table td {
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 13.5px;
            border-bottom: 1px solid #f1f5f9;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .invoice-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>

    {{-- Actions Toolbar (Hidden in Print) --}}
    <div class="container mb-3 no-print" style="max-width: 860px;">
        <div class="d-flex justify-content-between align-items-center">
            <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Return
            </a>
            <div class="d-flex gap-2">
                <button onclick="window.print()" class="btn btn-sm btn-dark">
                    <i class="bi bi-printer me-1"></i> Print / Save as PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Printable Invoice Card --}}
    <div class="invoice-card">
        {{-- Invoice Header --}}
        <div class="d-flex justify-content-between align-items-start border-bottom pb-4 mb-4">
            <div>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="brand-badge"><i class="bi bi-bag-check-fill"></i></span>
                    <span class="fs-4 fw-bold text-dark">{{ config('app.name', 'MONO') }}</span>
                </div>
                <p class="text-muted small mb-0">Curated Contemporary Goods & Essentials</p>
                <p class="text-muted small mb-0">support@{{ request()->getHost() }}</p>
            </div>

            <div class="text-end">
                <span class="badge bg-light text-dark border px-3 py-1 mb-2 fw-bold text-uppercase" style="letter-spacing: 0.5px;">
                    Official Receipt
                </span>
                <h4 class="fw-bold text-dark mb-0">#{{ $order->order_number }}</h4>
                <div class="text-muted small mt-1">Date: {{ $order->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        {{-- Recipient & Order Metadata --}}
        <div class="row g-4 mb-4">
            <div class="col-sm-6">
                <h6 class="text-uppercase small fw-bold text-muted mb-2">Billed & Shipped To</h6>
                <div class="fw-bold text-dark fs-6">{{ $order->customer_name }}</div>
                <div class="small text-secondary">{{ $order->shipping_address }}</div>
                <div class="small text-secondary">{{ $order->shipping_city }}, {{ $order->shipping_postcode }}</div>
                <div class="small text-secondary">{{ $order->shipping_country }}</div>
                <div class="small text-muted mt-1"><i class="bi bi-envelope me-1"></i> {{ $order->customer_email }}</div>
                <div class="small text-muted"><i class="bi bi-telephone me-1"></i> {{ $order->customer_phone }}</div>
            </div>

            <div class="col-sm-6 text-sm-end">
                <h6 class="text-uppercase small fw-bold text-muted mb-2">Payment & Shipment Info</h6>
                <div class="small mb-1">
                    <span class="text-muted">Payment Method:</span>
                    <strong class="text-dark">{{ $order->payment_method_label }}</strong>
                </div>
                <div class="small mb-1">
                    <span class="text-muted">Payment Status:</span>
                    @if ($order->payment_status === 'paid')
                        <span class="badge bg-success-subtle text-success border border-success-subtle fw-semibold">PAID</span>
                    @else
                        <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle fw-semibold text-uppercase">{{ $order->payment_status }}</span>
                    @endif
                </div>

                @if ($order->payment_reference)
                    <div class="small text-muted mb-1">
                        Ref: <code>{{ $order->payment_reference }}</code>
                    </div>
                @endif

                <div class="small mb-1">
                    <span class="text-muted">Fulfillment:</span>
                    <span class="badge bg-secondary-subtle text-dark fw-semibold text-uppercase">{{ $order->status }}</span>
                </div>

                @if ($order->carrier && $order->tracking_number)
                    <div class="small mt-2 p-2 bg-light rounded text-start text-sm-end border">
                        <div><strong class="text-dark">{{ $order->carrier }}</strong> Tracking:</div>
                        <code>{{ $order->tracking_number }}</code>
                    </div>
                @endif
            </div>
        </div>

        {{-- Line Items Table --}}
        <div class="table-responsive mb-4">
            <table class="table invoice-table">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <th>SKU</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Price</th>
                        <th class="text-end">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $item->product_name }}</div>
                                @if ($item->variant_name)
                                    <div class="small text-muted">{{ $item->variant_name }}</div>
                                @endif
                            </td>
                            <td>
                                <code class="small text-secondary">{{ $item->sku ?: '—' }}</code>
                            </td>
                            <td class="text-center fw-semibold">
                                {{ $item->quantity }}
                            </td>
                            <td class="text-end">
                                ${{ number_format($item->price, 2) }}
                            </td>
                            <td class="text-end fw-bold text-dark">
                                ${{ number_format($item->subtotal, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Subtotals & Grand Total --}}
        <div class="row justify-content-end mb-4">
            <div class="col-sm-6 col-md-5">
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-semibold text-dark">${{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="d-flex justify-content-between py-1 small">
                    <span class="text-muted">Shipping & Handling:</span>
                    <span class="fw-semibold text-success">
                        {{ $order->shipping_cost > 0 ? '$' . number_format($order->shipping_cost, 2) : 'FREE' }}
                    </span>
                </div>
                <div class="d-flex justify-content-between py-2 border-top border-dark border-2 mt-2">
                    <span class="fs-6 fw-bold text-dark">Total Due:</span>
                    <span class="fs-5 fw-bold text-dark">${{ number_format($order->total, 2) }}</span>
                </div>
            </div>
        </div>

        {{-- Customer Note (if any) --}}
        @if ($order->notes)
            <div class="p-3 bg-light rounded-3 mb-4 small">
                <strong class="d-block text-dark mb-1">Customer Notes:</strong>
                <span class="text-secondary">{{ $order->notes }}</span>
            </div>
        @endif

        {{-- Footer Acknowledgement --}}
        <div class="border-top pt-3 text-center text-muted small">
            <p class="mb-1">Thank you for your purchase with <strong>{{ config('app.name', 'MONO') }}</strong>!</p>
            <p class="mb-0 opacity-75">If you have any questions about this receipt, please contact our concierge at support@{{ request()->getHost() }}</p>
        </div>
    </div>

</body>
</html>
