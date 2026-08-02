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
                    <h2 class="text-xl font-bold mb-4">Verify Your Login</h2>
                    <p class="mb-4">We detected a new device. Choose how to receive your OTP:</p>
                    <form method="POST" action="{{ route('otp.send') }}">
                        @csrf
                        @if(session('otp_email'))
                            <button type="submit" name="method" value="email"
                                class="w-full mb-2 px-4 py-2 bg-blue-600 text-white rounded">
                                Send to Email
                            </button>
                        @endif

                        @if(session('otp_phone'))
                            <button disabled type="submit" name="method" value="phone"
                                class="w-full px-4 py-2 bg-green-600 text-white rounded">
                                Send to Phone
                            </button>
                        @endif
                    </form>
                </div>
            </main>
        </div>
    </body>

</html>
