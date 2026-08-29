<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- <title>HayagSync Security Alert</title> --}}
</head>

<body style="margin:0; padding:0; background-color:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#334155;">

    <div style="max-width:640px; margin:0 auto; padding:32px 16px;">

        <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:16px; overflow:hidden;">

            <div style="padding:24px; background:#0f172a; color:#ffffff;">
                <h1 style="margin:0; font-size:20px;">@yield('title')</h1>

                <p style="margin:6px 0 0; font-size:13px; color:#cbd5e1;"> @yield('sub-title')</p>
            </div>

            <div style="padding:28px 24px;">

                @yield('message')

                <p style="margin-bottom:0; font-size:12px; line-height:1.6; color:#64748b;">
                    This is an automated security notification from HayagSync.
                    Please do not reply to this email.
                </p>

            </div>

            <div style="padding:16px 24px; background:#f8fafc; border-top:1px solid #e2e8f0;">

                <p style="margin:0; font-size:11px; color:#94a3b8; text-align:center;">
                    © {{ date('Y') }} HayagSync. All rights reserved.
                </p>

            </div>

        </div>

    </div>

</body>
</html>
