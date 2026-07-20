<aside class="w-64 bg-gray-800 text-white min-h-screen flex flex-col">
    {{-- App Name --}}
    <div class="p-4 text-lg font-bold border-b border-gray-700">
        HayagSync
    </div>

    {{-- User Profile --}}
    <div class="p-4 flex items-center gap-3 border-b border-gray-700">
        <img src="{{ Auth::user()->profile_photo_url ?? asset('storage/images/unknown-user.webp') }}"
             alt="Profile"
             class="w-10 h-10 rounded-full object-cover">
        <div>
            <p class="text-sm font-semibold">{{ Auth::user()->last_name }}, {{ Auth::user()->first_name }}</p>
            <p class="text-xs text-gray-400">{{ Auth::user()->staff?->latestPosition()?->position_name ?? 'No Position' }}</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 mt-4 space-y-2">
        <a href="{{ route('dashboard') }}"
        class="block px-4 py-2 rounded
                {{ request()->routeIs('dashboard') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700' }}">
            Dashboard
        </a>

        <a href="{{ route('web.reports.index') }}"
        class="block px-4 py-2 rounded
                {{ request()->routeIs('web.reports.*') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700' }}">
            Report Management
        </a>

        <a href="{{ route('web.users.index') }}"
        class="block px-4 py-2 rounded
                {{ request()->routeIs('web.users.*') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700' }}">
            User List
        </a>

        <a href="{{ route('profile.edit') }}"
        class="block px-4 py-2 rounded
                {{ request()->routeIs('profile.edit') ? 'bg-gray-900 text-white' : 'hover:bg-gray-700' }}">
            Profile
        </a>
    </nav>


    {{-- Logout --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-auto">
        @csrf
        <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700 rounded">
            Logout
        </button>
    </form>
</aside>
