@extends('layouts.app')

@section('title', 'Activity Log - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Activity Log</h1>
            <p class="text-sm text-slate-500 mt-1">Audit trail of user actions.</p>
        </div>
    </div>

    {{-- Search Bar --}}
    <form method="GET" action="{{ route('web.activity_logs.index') }}" class="mb-4 flex gap-2">
        <input type="text" name="search" value="{{ $search }}"
               placeholder="Search logs..."
               class="flex-1 border rounded px-3 py-2">
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Search</button>
    </form>

    {{-- Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="min-w-full divide-y divide-slate-200">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">
                        <a href="{{ route('web.activity_logs.index', ['sort' => 'user_id', 'order' => $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}">
                            User
                        </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">
                        <a href="{{ route('web.activity_logs.index', ['sort' => 'action_type', 'order' => $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}">
                            Action Type
                        </a>
                    </th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Description</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Module</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">Record ID</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">IP Address</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold text-slate-600">
                        <a href="{{ route('web.activity_logs.index', ['sort' => 'created_at', 'order' => $sortOrder === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}">
                            Date
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($logs as $log)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-2 text-sm text-blue-600">
                            <a href="{{ route('web.users.show', $log->user_id) }}">
                                {{ $log->user?->last_name }}, {{ $log->user?->first_name }}
                            </a>
                        </td>
                        <td class="px-4 py-2 text-sm text-slate-700">{{ $log->action_type }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $log->description }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $log->module }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $log->record_id }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $log->ip_address }}</td>
                        <td class="px-4 py-2 text-sm text-slate-600">{{ $log->created_at->format('M d, Y h:i A') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-slate-500">No activity logs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        {{-- <div class="px-5 py-4 border-t border-slate-200 bg-white">
            {{ $logs->appends(['search' => $search, 'sort' => $sortField, 'order' => $sortOrder])->links() }}
        </div> --}}
    </div>
</div>
@endsection
