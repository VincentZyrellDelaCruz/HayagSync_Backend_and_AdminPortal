<x-guest-layout>

    <div class="w-full max-w-md">

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-slate-800">
                HayagSync
            </h1>

            <p class="mt-2 text-slate-500 text-sm">
                Smart Mobile Incident Support Application
            </p>
        </div>

        <!-- Card -->
        <div class="bg-white shadow-2xl rounded-2xl border border-gray-100 p-8">

            <!-- Session Status -->
            <x-auth-session-status
                class="mb-4"
                :status="session('status')"
            />

            <!-- Validation Errors -->
            @if ($errors->any())
                <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">
                    <ul class="list-disc list-inside text-sm text-red-600">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <x-input-label
                        for="email"
                        :value="__('Email Address')"
                        class="mb-2 font-medium"
                    />

                    <x-text-input
                        id="email"
                        class="block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        type="email"
                        name="email"
                        :value="old('email')"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="Enter your email"
                    />

                    <x-input-error
                        :messages="$errors->get('email')"
                        class="mt-2"
                    />
                </div>

                <!-- Password -->
                <div>
                    <x-input-label
                        for="password"
                        :value="__('Password')"
                        class="mb-2 font-medium"
                    />

                    <x-text-input
                        id="password"
                        class="block w-full rounded-xl border-gray-300 focus:border-blue-500 focus:ring-blue-500"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                        placeholder="Enter your password"
                    />

                    <x-input-error
                        :messages="$errors->get('password')"
                        class="mt-2"
                    />
                </div>

                <!-- Forgot Password -->
                @if (Route::has('password.request'))
                    <div class="flex justify-end">
                        <a
                            href="{{ route('password.request') }}"
                            class="text-sm text-blue-600 hover:text-blue-700 hover:underline"
                        >
                            Forgot Password?
                        </a>
                    </div>
                @endif

                <!-- Login Button -->
                <div>
                    <x-primary-button
                        class="w-full justify-center rounded-xl py-3 bg-blue-600 hover:bg-blue-700 text-base font-semibold transition duration-200 shadow-lg"
                    >
                        Log In
                    </x-primary-button>
                </div>

            </form>

        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-xs text-gray-500">
            © {{ date('Y') }} HayagSync. All rights reserved.
        </div>

    </div>

</x-guest-layout>
