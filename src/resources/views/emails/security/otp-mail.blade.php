@extends('emails.layouts.template')

@section('title', 'HayagSync OTP')

@section('sub-title', 'Email Verification Code')

@section('message')
    <p style="margin-top:0; font-size:15px;"> Hello!</p>

    <p style="font-size:14px; line-height:1.7;"> HayagSync detected login in different devices or browser.</p>

    <div style="
        margin:24px 0;
        padding:20px;
        border-radius:12px;
        background:#f8fafc;
        border:1px solid #e2e8f0;
        text-align: center;
        ">

        <p style="margin:8px 0; font-size: 20px; font-weight: bold;">
            {{ $otp }}
        </p>

    </div>

    <div style="margin:24px 0; padding:16px; border-left:4px solid #e11d48; background:#fff1f2;">

        <p style="margin:0; font-size:14px; line-height:1.7;">
            {{ $securityEvent->description }}
        </p>

    </div>

    <p style="font-size:14px; line-height:1.7;">
        This code will expire in 5 minutes.
    </p>

    <p style="font-size:14px; line-height:1.7;">
        If you did not request this verification, please ignore this message.
    </p>

@endsection
