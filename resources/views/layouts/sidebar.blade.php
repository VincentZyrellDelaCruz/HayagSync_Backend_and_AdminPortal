<aside class="w-64 bg-gray-800 text-white">
    <div class="p-4 text-lg font-bold">App Name</div>
    <nav class="mt-4 space-y-2">
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 hover:bg-gray-700">Dashboard</a>
        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 hover:bg-gray-700">Profile</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700">Logout</button>
        </form>
    </nav>
</aside>

