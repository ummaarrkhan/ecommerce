@extends('emails.layout')

@section('title', 'Order Confirmation #' . $order->order_number . ' | ' . config('app.name', 'MONO'))

@section('content')
    <!-- Greeting & Status Banner -->
    <div style="text-align: center; margin-bottom: 28px;">
        <div style="display: inline-block; width: 56px; height: 56px; line-height: 56px; border-radius: 50%; background-color: #ecfdf5; color: #059669; font-size: 26px; font-weight: bold; margin-bottom: 12px;">
            &#10003;
        </div>
        <h1 style="margin: 0 0 8px 0; font-size: 22px; font-weight: 800; color: #0f172a;">
            Thank you for your order!
        </h1>
        <p style="margin: 0; font-size: 14px; color: #64748b; line-height: 1.5;">
            Hi <strong>{{ $order->customer_name }}</strong>, we’ve received your order and are getting it ready for delivery.
        </p>
    </div>

    <!-- Order Metadata Card -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 24px;">
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
                        <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Order Date:</td>
                        <td align="right" style="font-size: 13px; font-weight: 600; color: #0f172a; padding-bottom: 6px;">
                            {{ $order->created_at->format('M d, Y h:i A') }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Payment Method:</td>
                        <td align="right" style="font-size: 13px; font-weight: 600; color: #0f172a; padding-bottom: 6px;">
                            {{ $order->payment_method_label }}
                        </td>
                    </tr>
                    @if ($order->estimated_delivery)
                        <tr>
                            <td style="font-size: 13px; color: #64748b;">Estimated Delivery:</td>
                            <td align="right" style="font-size: 13px; font-weight: 700; color: #2563eb;">
                                {{ $order->estimated_delivery->format('M d, Y') }}
                            </td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <!-- Shipping Address & Details -->
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 10px 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #475569;">
            Shipping To
        </h2>
        <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; font-size: 13px; color: #334155; line-height: 1.6;">
            <strong>{{ $order->customer_name }}</strong><br>
            {{ $order->shipping_address }}<br>
            {{ $order->shipping_city }}, {{ $order->shipping_postcode }}<br>
            {{ $order->shipping_country }}<br>
            <span style="color: #64748b;">Phone: {{ $order->customer_phone }}</span>
        </div>
    </div>

    <!-- Items Table -->
    <div style="margin-bottom: 24px;">
        <h2 style="margin: 0 0 12px 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #475569;">
            Order Items ({{ $order->items->count() }})
        </h2>
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse; border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
            <thead>
                <tr style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                    <th align="left" style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Item</th>
                    <th align="center" style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Qty</th>
                    <th align="right" style="padding: 10px 14px; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 12px 14px; vertical-align: top;">
                            <div style="font-weight: 600; font-size: 14px; color: #0f172a;">
                                {{ $item->product_name }}
                            </div>
                            @if ($item->variant_name)
                                <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                                    Option: {{ $item->variant_name }}
                                </div>
                            @endif
                            <div style="font-size: 12px; color: #94a3b8; margin-top: 2px;">
                                ${{ number_format((float) $item->price, 2) }} each
                            </div>
                        </td>
                        <td align="center" style="padding: 12px 14px; font-size: 13px; font-weight: 600; color: #334155; vertical-align: top;">
                            &times; {{ $item->quantity }}
                        </td>
                        <td align="right" style="padding: 12px 14px; font-size: 14px; font-weight: 700; color: #0f172a; vertical-align: top;">
                            ${{ number_format((float) $item->subtotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Price Calculation Breakdown -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="margin-bottom: 28px;">
        <tr>
            <td align="right">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 260px;">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding: 4px 0;">Subtotal:</td>
                        <td align="right" style="font-size: 13px; font-weight: 600; color: #0f172a; padding: 4px 0;">
                            ${{ number_format((float) $order->subtotal, 2) }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding: 4px 0;">Delivery / Shipping:</td>
                        <td align="right" style="font-size: 13px; font-weight: 600; color: {{ (float) $order->shipping_cost > 0 ? '#0f172a' : '#10b981' }}; padding: 4px 0;">
                            @if ((float) $order->shipping_cost > 0)
                                ${{ number_format((float) $order->shipping_cost, 2) }}
                            @else
                                FREE
                            @endif
                        </td>
                    </tr>
                    <tr style="border-top: 2px solid #0f172a;">
                        <td style="font-size: 16px; font-weight: 800; color: #0f172a; padding-top: 10px;">Grand Total:</td>
                        <td align="right" style="font-size: 18px; font-weight: 800; color: #0f172a; padding-top: 10px;">
                            ${{ number_format((float) $order->total, 2) }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Call to Action Button -->
    <div style="text-align: center; margin-bottom: 24px;">
        <a href="{{ route('store.orders.invoice', $order->order_number) }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 14px 28px; border-radius: 8px; box-shadow: 0 4px 10px rgba(15, 23, 42, 0.15);">
            View Order & Download Invoice &rarr;
        </a>
    </div>

    @if ($order->notes)
        <div style="background-color: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px 16px; font-size: 12px; color: #92400e;">
            <strong>Order Notes:</strong> {{ $order->notes }}
        </div>
    @endif
@endsection
