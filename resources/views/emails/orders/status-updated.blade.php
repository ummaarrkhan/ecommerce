@extends('emails.layout')

@section('title', 'Order Update #' . $order->order_number . ' | ' . config('app.name', 'SHOPX'))

@section('content')
    @php
        $status = strtolower($order->status);
        $statusConfigs = [
            'shipped' => [
                'title' => 'Your order is on the way!',
                'badge' => 'Shipped',
                'bg' => '#eff6ff',
                'color' => '#1d4ed8',
                'icon' => '&#128666;',
                'description' => 'Great news! Your package has been dispatched and is on its journey to you.',
            ],
            'completed' => [
                'title' => 'Your order has been delivered!',
                'badge' => 'Delivered',
                'bg' => '#ecfdf5',
                'color' => '#047857',
                'icon' => '&#10003;',
                'description' => 'Your package has arrived! We hope you love your new purchase.',
            ],
            'processing' => [
                'title' => 'Your order is being prepared',
                'badge' => 'Processing',
                'bg' => '#fef3c7',
                'color' => '#b45309',
                'icon' => '&#9881;',
                'description' => 'Our fulfillment warehouse is carefully packing your items for dispatch.',
            ],
            'cancelled' => [
                'title' => 'Your order has been cancelled',
                'badge' => 'Cancelled',
                'bg' => '#fef2f2',
                'color' => '#b91c1c',
                'icon' => '&#10007;',
                'description' => 'Your order has been cancelled. If payment was made, your refund is being processed.',
            ],
        ];

        $currentConfig = $statusConfigs[$status] ?? [
            'title' => 'Status update on your order',
            'badge' => ucfirst($order->status),
            'bg' => '#f1f5f9',
            'color' => '#334155',
            'icon' => '&#128230;',
            'description' => 'There is a new update regarding your order status.',
        ];
    @endphp

    <!-- Status Header Banner -->
    <div style="text-align: center; margin-bottom: 28px;">
        <div style="display: inline-block; padding: 6px 16px; border-radius: 9999px; background-color: {{ $currentConfig['bg'] }}; color: {{ $currentConfig['color'] }}; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
            Order Status: {{ $currentConfig['badge'] }}
        </div>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a;">
            {{ $currentConfig['title'] }}
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
            Hi {{ $order->customer_name }}, {{ $currentConfig['description'] }}
        </p>
    </div>

    <!-- Tracking Details Box (if available) -->
    @if ($order->tracking_number || $order->carrier || $order->live_tracking_url)
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #bfdbfe; border-radius: 12px; margin-bottom: 24px;">
            <tr>
                <td style="padding: 20px 24px;">
                    <div style="font-size: 12px; font-weight: 700; color: #1e40af; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 10px;">
                        Shipment & Tracking Information
                    </div>
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        @if ($order->carrier)
                            <tr>
                                <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Courier / Carrier:</td>
                                <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a; padding-bottom: 6px;">
                                    {{ $order->carrier }}
                                </td>
                            </tr>
                        @endif
                        @if ($order->tracking_number)
                            <tr>
                                <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Tracking Number:</td>
                                <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a; padding-bottom: 6px; font-family: monospace;">
                                    {{ $order->tracking_number }}
                                </td>
                            </tr>
                        @endif
                        @if ($order->estimated_delivery)
                            <tr>
                                <td style="font-size: 13px; color: #64748b;">Estimated Delivery:</td>
                                <td align="right" style="font-size: 13px; font-weight: 700; color: #2563eb;">
                                    {{ $order->estimated_delivery->format('M d, Y') }}
                                </td>
                            </tr>
                        @endif
                    </table>

                    @if ($order->live_tracking_url)
                        <div style="margin-top: 16px; text-align: center;">
                            <a href="{{ $order->live_tracking_url }}" target="_blank" style="display: inline-block; background-color: #2563eb; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 13px; padding: 10px 20px; border-radius: 6px;">
                                Track Package with Carrier &rarr;
                            </a>
                        </div>
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <!-- Order Summary Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
        <tr>
            <td style="padding: 16px 20px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Order Number:</td>
                        <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a; padding-bottom: 6px;">
                            #{{ $order->order_number }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Order Total:</td>
                        <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a; padding-bottom: 6px;">
                            ${{ number_format((float) $order->total, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #64748b;">Shipping Destination:</td>
                        <td align="right" style="font-size: 13px; font-weight: 600; color: #334155;">
                            {{ $order->shipping_city }}, {{ $order->shipping_country }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Items Quick List -->
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #64748b;">
            Items in this order:
        </h2>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #334155; line-height: 1.8;">
            @foreach ($order->items as $item)
                <li>
                    <strong>{{ $item->product_name }}</strong>
                    @if ($item->variant_name)
                        <span style="color: #64748b;">({{ $item->variant_name }})</span>
                    @endif
                    &times; {{ $item->quantity }}
                </li>
            @endforeach
        </ul>
    </div>

    <!-- Action Link -->
    <div style="text-align: center; margin-top: 28px;">
        <a href="{{ route('store.orders.invoice', $order->order_number) }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 13px 26px; border-radius: 8px;">
            View Complete Order Details &rarr;
        </a>
    </div>
@endsection
