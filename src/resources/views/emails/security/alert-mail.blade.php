@extends('emails.layouts.template')

@section('title', 'HayagSync Security Alert')

@section('sub-title', 'Automated security notification')

@section('message')
    <p style="margin-top:0; font-size:15px;"> Hello {{ $admin->first_name }},</p>

    <p style="font-size:14px; line-height:1.7;"> HayagSync detected repeated failed login attempts that require administrative attention.</p>

    <div style="margin:24px 0; padding:20px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0;">

        <p style="margin:0 0 12px;">
            <strong>Severity:</strong>

            <span style="
                display:inline-block;
                margin-left:6px;
                padding:4px 10px;
                border-radius:999px;
                font-size:12px;
                font-weight:bold;
                color:#ffffff;
                background-color:
                {{ strtolower($securityEvent->severity) === 'critical'
                    ? '#dc2626'
                    : '#ea580c' }};
            ">
                {{ ucfirst($securityEvent->severity) }}
            </span>
        </p>

        <p style="margin:8px 0;">
            <strong>Event:</strong>
            {{ $securityEvent->event_type }}
        </p>

        <p style="margin:8px 0;">
            <strong>IP Address:</strong>
            {{ $securityEvent->ip_address ?? 'Unknown' }}
        </p>

        <p style="margin:8px 0;">
            <strong>Detected:</strong>
            {{ $securityEvent->created_at?->format('M d, Y h:i A') }}
        </p>

        <p style="margin:8px 0;">
            <strong>Status:</strong>
            {{ ucfirst($securityEvent->status) }}
        </p>

    </div>

    <div style="margin:24px 0; padding:16px; border-left:4px solid #e11d48; background:#fff1f2;">

        <p style="margin:0; font-size:14px; line-height:1.7;">
            {{ $securityEvent->description }}
        </p>

    </div>

    <p style="font-size:14px; line-height:1.7;">
        Please review the event in the HayagSync Security Center
        and take appropriate administrative action.
    </p>

    <div style="margin:28px 0; text-align:center;">

        <a
            href="{{ route('web.admin.security.index', [
                'tab' => 'security',
                'status' => 'open',
            ]) }}"
            style="
                display:inline-block;
                padding:12px 22px;
                background:#2563eb;
                color:#ffffff;
                text-decoration:none;
                border-radius:10px;
                font-size:14px;
                font-weight:bold;
            "
        >
            Open Security Center
        </a>

    </div>
@endsection
