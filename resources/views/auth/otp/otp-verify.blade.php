<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>OTP Verification</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased bg-gray-100 text-gray-900">
        <div class="flex">
            {{-- Main Content --}}
            <main class="flex-1 p-6">
                <div class="max-w-md mx-auto mt-10 bg-white p-6 rounded shadow">
                    <h2 class="text-xl font-bold mb-4">Enter OTP</h2>
                    @if($errors->any())
                        <div class="mb-4 text-red-600">
                            {{ $errors->first('otp_code') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('otp.verify.submit') }}">
                        @csrf
                        <input type="text" name="otp_code" placeholder="6-digit code"
                            class="w-full border rounded px-3 py-2 mb-4">
                        <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded">
                            Verify
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </body>

</html>
