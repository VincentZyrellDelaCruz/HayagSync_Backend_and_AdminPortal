@extends('layouts.app')

@section('title', 'Incident Panel - HayagSync')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Incident Inbox</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Review, prioritize, and organize reported incidents.
                </p>
            </div>

            <div class="flex items-center gap-2 text-sm">
                <span class="px-3 py-1 rounded-full bg-red-100 text-red-700 font-medium">
                    Pending First
                </span>
                <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">
                    Urgent Highlighted
                </span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sidebar Filters --}}
            <aside class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Filters</h2>
                        <p class="text-xs text-slate-500 mt-1">Organize cases faster</p>
                    </div>

                    <div class="p-5 space-y-6">
                        {{-- Reset --}}
                        <div>
                            <a href="{{ route('web.incidents.index') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                                Reset Filters
                            </a>
                        </div>

                        {{-- Status Filter --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">
                                Current Status
                            </h3>

                            <div class="space-y-1">
                                @foreach($statuses as $status)
                                    @php
                                        $isActive = $selectedStatus === $status;

                                        $statusLabel = $status === 'all' ? 'All Statuses' : $status;

                                        $statusClasses = $isActive
                                            ? 'bg-slate-900 text-white'
                                            : 'text-slate-700 hover:bg-slate-100';

                                        $query = array_filter([
                                            'status' => $status === 'all' ? null : $status,
                                            'category' => $selectedCategory !== 'all' ? $selectedCategory : null,
                                        ]);
                                    @endphp

                                    <a href="{{ route('web.incidents.index', $query) }}"
                                       class="flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition {{ $statusClasses }}">
                                        <span>{{ $statusLabel }}</span>

                                        @if($status === 'Pending')
                                            <span class="text-xs {{ $isActive ? 'text-red-200' : 'text-red-500' }}">●</span>
                                        @elseif($status === 'Under Investigation')
                                            <span class="text-xs {{ $isActive ? 'text-orange-200' : 'text-orange-500' }}">●</span>
                                        @elseif($status === 'Resolved')
                                            <span class="text-xs {{ $isActive ? 'text-green-200' : 'text-green-500' }}">●</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Category Filter --}}
                        <div>
                            <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">
                                Incident Category
                            </h3>

                            <div class="space-y-1">
                                @php
                                    $allCategoryActive = $selectedCategory === 'all';
                                    $allCategoryQuery = array_filter([
                                        'status' => $selectedStatus !== 'all' ? $selectedStatus : null,
                                    ]);
                                @endphp

                                <a href="{{ route('web.incidents.index', $allCategoryQuery) }}"
                                   class="block px-3 py-2 rounded-xl text-sm font-medium transition {{ $allCategoryActive ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                    All Categories
                                </a>

                                @foreach($categories as $category)
                                    @php
                                        $isCategoryActive = (string) $selectedCategory === (string) $category->id;

                                        $query = array_filter([
                                            'status' => $selectedStatus !== 'all' ? $selectedStatus : null,
                                            'category' => $category->id,
                                        ]);
                                    @endphp

                                    <a href="{{ route('web.incidents.index', $query) }}"
                                       class="block px-3 py-2 rounded-xl text-sm font-medium transition {{ $isCategoryActive ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100' }}">
                                        {{ $category->category_name }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Incident List --}}
            <section class="lg:col-span-9">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    {{-- Top Bar --}}
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-700">
                                    Filtered Incidents
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $incidents->total() }} total incident{{ $incidents->total() !== 1 ? 's' : '' }}
                                </p>
                            </div>

                            {{-- Active Filter Badges --}}
                            <div class="flex flex-wrap gap-2">
                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                    Status: {{ $selectedStatus === 'all' ? 'All' : $selectedStatus }}
                                </span>

                                <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                    Category:
                                    {{
                                        $selectedCategory === 'all'
                                            ? 'All'
                                            : optional($categories->firstWhere('id', $selectedCategory))->category_name
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- List --}}
                    <div class="divide-y divide-slate-200">
                        @forelse($incidents as $incident)
                            @php
                                $status = $incident->current_status?->status_name ?? 'No Status';
                                $urgency = strtolower($incident->urgency_level ?? 'low');

                                $isPending = in_array($status, ['Pending', 'Under Investigation']);
                                $isUrgent = in_array($urgency, ['critical', 'high']);

                                $rowClasses = 'hover:bg-slate-50 transition duration-150';
                                $titleClasses = 'text-slate-800';
                                $badgeClasses = 'bg-slate-100 text-slate-700';
                                $urgencyClasses = 'bg-slate-100 text-slate-700';

                                if ($status === 'Pending' && $urgency === 'critical') {
                                    $rowClasses .= ' bg-red-50 border-l-4 border-red-500';
                                    $titleClasses = 'text-red-800 font-bold';
                                } elseif ($status === 'Pending' && $urgency === 'high') {
                                    $rowClasses .= ' bg-amber-50 border-l-4 border-amber-500';
                                    $titleClasses = 'text-amber-900 font-semibold';
                                } elseif ($status === 'Under Investigation' && $isUrgent) {
                                    $rowClasses .= ' bg-orange-50 border-l-4 border-orange-400';
                                    $titleClasses = 'text-orange-900 font-semibold';
                                }

                                $badgeClasses = match ($status) {
                                    'Pending' => 'bg-red-100 text-red-700',
                                    'Under Investigation' => 'bg-orange-100 text-orange-700',
                                    'Scheduled' => 'bg-blue-100 text-blue-700',
                                    'Resolved' => 'bg-green-100 text-green-700',
                                    'Unresolved' => 'bg-rose-100 text-rose-700',
                                    'Cancelled' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };

                                $urgencyClasses = match ($urgency) {
                                    'critical' => 'bg-red-100 text-red-700',
                                    'high' => 'bg-amber-100 text-amber-700',
                                    'medium' => 'bg-blue-100 text-blue-700',
                                    'low' => 'bg-slate-100 text-slate-700',
                                    default => 'bg-slate-100 text-slate-700',
                                };

                                $reporter = $incident->user
                                    ? $incident->user->last_name . ', ' . $incident->user->first_name
                                    : 'Unknown Reporter';
                            @endphp

                            <a href="{{ route('incidents.show', $incident->id) }}"
                               class="block px-5 py-4 {{ $rowClasses }}">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                    {{-- Left Section --}}
                                    <div class="flex items-start gap-4 min-w-0 flex-1">
                                        {{-- Priority Dot --}}
                                        <div class="mt-1 shrink-0">
                                            @if($status === 'Pending' && $urgency === 'critical')
                                                <span class="w-3 h-3 rounded-full bg-red-500 block"></span>
                                            @elseif($status === 'Pending' && $urgency === 'high')
                                                <span class="w-3 h-3 rounded-full bg-amber-500 block"></span>
                                            @elseif($status === 'Under Investigation')
                                                <span class="w-3 h-3 rounded-full bg-orange-400 block"></span>
                                            @elseif($status === 'Resolved')
                                                <span class="w-3 h-3 rounded-full bg-green-500 block"></span>
                                            @else
                                                <span class="w-3 h-3 rounded-full bg-slate-300 block"></span>
                                            @endif
                                        </div>

                                        {{-- Main Info --}}
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <h3 class="text-sm sm:text-base truncate {{ $titleClasses }}">
                                                    {{ $incident->incident_title }}
                                                </h3>

                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                                    {{ $status }}
                                                </span>

                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase {{ $urgencyClasses }}">
                                                    {{ $incident->urgency_level ?? 'Low' }}
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                                <span>
                                                    <span class="font-medium text-slate-600">Reported by:</span>
                                                    {{ $reporter }}
                                                </span>

                                                @if($incident->category)
                                                    <span>
                                                        <span class="font-medium text-slate-600">Category:</span>
                                                        {{ $incident->category->category_name }}
                                                    </span>
                                                @endif

                                                @if($incident->location)
                                                    <span class="truncate">
                                                        <span class="font-medium text-slate-600">Location:</span>
                                                        {{ $incident->location }}
                                                    </span>
                                                @endif
                                            </div>

                                            @if($incident->description)
                                                <p class="mt-2 text-sm text-slate-500 line-clamp-1">
                                                    {{ $incident->description }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Right Section --}}
                                    <div class="flex lg:flex-col items-start lg:items-end justify-between gap-2 shrink-0 text-sm">
                                        <div class="text-slate-700 font-medium">
                                            {{ $incident->created_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $incident->created_at->format('h:i A') }}
                                        </div>

                                        @if($isPending && $isUrgent)
                                            <span class="mt-1 inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                                Needs Attention
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="px-6 py-16 text-center">
                                <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-700">No incidents found</h3>
                                <p class="text-sm text-slate-500 mt-2">
                                    No cases match the selected filters.
                                </p>
                            </div>
                        @endforelse
                    </div>

                    {{-- Pagination --}}
                    @if($incidents->hasPages())
                        <div class="px-5 py-4 border-t border-slate-200 bg-white">
                            {{ $incidents->links() }}
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
@endsection
