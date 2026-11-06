@extends('emails.layout')

@section('title', 'Welcome to ' . config('app.name', 'MONO') . ', ' . $user->name . '!')

@section('content')
    <!-- Welcome Header -->
    <div style="text-align: center; margin-bottom: 28px;">
        <div style="display: inline-block; width: 60px; height: 60px; line-height: 60px; border-radius: 50%; background-color: #eff6ff; color: #2563eb; font-size: 28px; margin-bottom: 12px;">
            &#127881;
        </div>
        <h1 style="margin: 0 0 8px 0; font-size: 24px; font-weight: 800; color: #0f172a;">
            Welcome to {{ config('app.name', 'MONO') }}!
        </h1>
        <p style="margin: 0; font-size: 15px; color: #64748b; line-height: 1.5;">
            Hi <strong>{{ $user->name }}</strong>, your account is all set up and ready to go.
        </p>
    </div>

    <!-- Account Details Box -->
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 28px;">
        <tr>
            <td style="padding: 18px 20px;">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tr>
                        <td style="font-size: 13px; color: #64748b; padding-bottom: 6px;">Registered Name:</td>
                        <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a; padding-bottom: 6px;">
                            {{ $user->name }}
                        </td>
                    </tr>
                    <tr>
                        <td style="font-size: 13px; color: #64748b;">Login Email:</td>
                        <td align="right" style="font-size: 13px; font-weight: 700; color: #0f172a;">
                            {{ $user->email }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- Perks Section -->
    <div style="margin-bottom: 28px;">
        <h2 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #475569;">
            What you can do with your account:
        </h2>
        
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td style="padding-bottom: 14px; vertical-align: top;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="vertical-align: top; padding-right: 12px; font-size: 18px;">&#9889;</td>
                            <td>
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                    Express Checkout
                                </div>
                                <div style="font-size: 13px; color: #64748b; line-height: 1.4;">
                                    Check out faster with pre-filled shipping info and unified order management.
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="padding-bottom: 14px; vertical-align: top;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="vertical-align: top; padding-right: 12px; font-size: 18px;">&#128230;</td>
                            <td>
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                    Live Order Tracking
                                </div>
                                <div style="font-size: 13px; color: #64748b; line-height: 1.4;">
                                    Follow your package in real-time and download itemized invoices anytime.
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">
                    <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="vertical-align: top; padding-right: 12px; font-size: 18px;">&#10084;</td>
                            <td>
                                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-bottom: 2px;">
                                    Saved Wishlist
                                </div>
                                <div style="font-size: 13px; color: #64748b; line-height: 1.4;">
                                    Save your favorite dresses and styles to purchase when you are ready.
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- CTA Button -->
    <div style="text-align: center; margin-bottom: 20px;">
        <a href="{{ route('store.home') }}" style="display: inline-block; background-color: #0f172a; color: #ffffff; text-decoration: none; font-weight: 700; font-size: 14px; padding: 14px 32px; border-radius: 8px; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.15);">
            Start Shopping Now &rarr;
        </a>
    </div>
@endsection
