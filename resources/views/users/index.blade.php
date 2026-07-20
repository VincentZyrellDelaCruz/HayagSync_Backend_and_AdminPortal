@extends('layouts.app')

@section('title', 'User Management - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">User Directory</h1>
            <p class="text-sm text-slate-500 mt-1">Browse and manage users by role.</p>
        </div>
    </div>

    {{-- Filter Buttons --}}
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('web.users.index', ['filter' => 'parent_guardian']) }}"
            class="px-4 py-2 rounded {{ $filter==='parent_guardian' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Parent/Guardian
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'staff']) }}"
           class="px-4 py-2 rounded {{ $filter==='staff' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
           Staff
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'adviser']) }}"
           class="px-4 py-2 rounded {{ $filter==='adviser' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
           Adviser/Teacher
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'tagasubaybay']) }}"
           class="px-4 py-2 rounded {{ $filter==='tagasubaybay' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
           Ministrong Tagasubaybay
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'principal']) }}"
           class="px-4 py-2 rounded {{ $filter==='principal' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
           Principal
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'osd_officer']) }}"
           class="px-4 py-2 rounded {{ $filter==='osd_officer' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
           OSD Officer
        </a>
        <a href="{{ route('web.users.index', ['filter' => 'admin']) }}"
            class="px-4 py-2 rounded {{ $filter==='admin' ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            Admin
        </a>
    </div>

    {{-- Search Bar --}}
    <form method="GET" action="{{ route('web.users.index') }}" class="mb-4 flex gap-2">
        <input type="hidden" name="filter" value="{{ $filter }}">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search users..."
               class="flex-1 border rounded px-3 py-2">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Search</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Name</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Email</th>

                    @if($filter !== 'parent_guardian')
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Position</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Department</th>
                    @else
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Parent Code</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Occupation</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($users as $user)
                    <tr class="hover:bg-slate-50 cursor-pointer"
                        onclick="window.location='{{ route('web.users.show', $user->id) }}'">
                        <td class="px-4 py-2 text-sm text-slate-800">
                            {{ $user->last_name }}, {{ $user->first_name }}
                        </td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $user->email }}</td>

                        @if($filter !== 'parent_guardian')
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ $user->staff?->latestPosition()?->position_name ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ $user->staff?->department ?? 'N/A' }}
                            </td>
                        @else
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ $user->parent_guardian?->parent_code ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-2 text-sm text-slate-600">
                                {{ $user->parent_guardian?->occupation ?? 'N/A' }}
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-6 text-center text-slate-500">No users found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        {{-- <div class="px-5 py-4 border-t border-slate-200 bg-white">
            {{ $users->appends(['filter' => $filter, 'search' => request('search')])->links() }}
        </div> --}}
    </div>
</div>
@endsection
