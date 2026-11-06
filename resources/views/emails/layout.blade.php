<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>@yield('title', config('app.name', 'MONO'))</title>
    <style>
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        body { margin: 0; padding: 0; width: 100% !important; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b; }
        @media screen and (max-width: 620px) {
            .email-container { width: 100% !important; }
            .mobile-stack { display: block !important; width: 100% !important; }
            .mobile-hide { display: none !important; }
            .p-responsive { padding: 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f1f5f9; padding: 30px 10px;">
        <tr>
            <td align="center">
                <!-- Main Container (Max 600px) -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" class="email-container" style="max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 14px rgba(15, 23, 42, 0.05); border: 1px solid #e2e8f0;">
                    
                    <!-- Header -->
                    <tr>
                        <td align="center" style="background-color: #0f172a; padding: 28px 24px;">
                            <a href="{{ route('store.home') }}" style="text-decoration: none; display: inline-block;">
                                <span style="font-size: 24px; font-weight: 800; letter-spacing: 1.5px; color: #ffffff; text-transform: uppercase;">
                                    {{ config('app.name', 'MONO') }}
                                </span>
                            </a>
                            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #94a3b8; margin-top: 4px;">
                                Premium Fashion & Lifestyle
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td class="p-responsive" style="padding: 36px 32px;">
                            @yield('content')
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 24px 32px; border-top: 1px solid #e2e8f0; text-align: center;">
                            <p style="margin: 0 0 10px 0; font-size: 13px; color: #64748b;">
                                Need help with your order? Our team is here for you.
                            </p>
                            <p style="margin: 0 0 16px 0; font-size: 13px;">
                                <a href="{{ route('store.home') }}" style="color: #2563eb; text-decoration: none; font-weight: 600; margin: 0 8px;">Storefront</a> &bull;
                                <a href="{{ route('store.account') }}" style="color: #2563eb; text-decoration: none; font-weight: 600; margin: 0 8px;">My Account</a>
                            </p>
                            <p style="margin: 0; font-size: 11px; color: #94a3b8; line-height: 1.5;">
                                &copy; {{ date('Y') }} {{ config('app.name', 'MONO') }}. All rights reserved.<br>
                                This is an automated transactional message regarding your account or order.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
