@extends('layouts.app')

@section('title', 'Incident Details - HayagSync')

@section('content')
    @php
        $latestUpdate = $incident->latest_update;
        $status = $latestUpdate?->incident_status?->status_name ?? 'No Status';
        $urgency = strtolower($incident->urgency_level ?? 'low');

        $latestReviewer = $latestUpdate?->user;
        $latestReviewerStaff = $latestReviewer?->staff;

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

        $reviewerName = $latestReviewer
            ? trim($latestReviewer->first_name . ' ' . $latestReviewer->last_name)
            : null;
    @endphp

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back --}}
        <div class="mb-6">
            <a href="{{ route('web.incidents.index') }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Incident Inbox
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-6">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2 mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                {{ $status }}
                            </span>

                            <span class="px-3 py-1 rounded-full text-xs font-medium uppercase {{ $urgencyClasses }}">
                                {{ $incident->urgency_level ?? 'Low' }}
                            </span>

                            @if(in_array($status, ['Pending', 'Under Investigation']) && in_array($urgency, ['critical', 'high']))
                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    Needs Attention
                                </span>
                            @endif
                        </div>

                        <h1 class="text-2xl font-bold text-slate-800">
                            {{ $incident->incident_title }}
                        </h1>

                        <p class="text-sm text-slate-500 mt-2">
                            Detailed incident case overview, involved students, and review timeline.
                        </p>
                    </div>

                    <div class="text-sm text-slate-500 lg:text-right shrink-0">
                        <p class="font-medium text-slate-700">
                            {{ $incident->created_at->format('M d, Y') }}
                        </p>
                        <p>{{ $incident->created_at->format('h:i A') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Main Content --}}
            <section class="lg:col-span-8 space-y-6">
                {{-- Incident Overview --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Incident Overview</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Reported By</p>
                            <p class="text-sm text-slate-800">{{ $reporter }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Category</p>
                            <p class="text-sm text-slate-800">{{ $incident->category?->category_name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Incident Date & Time</p>
                            <p class="text-sm text-slate-800">
                                {{ $incident->incident_datetime ? \Carbon\Carbon::parse($incident->incident_datetime)->format('M d, Y h:i A') : 'Not specified' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Location</p>
                            <p class="text-sm text-slate-800">{{ $incident->location ?? 'Not specified' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Incident Description</h2>
                    </div>

                    <div class="p-6">
                        @if($incident->description)
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                {{ $incident->description }}
                            </p>
                        @else
                            <p class="text-sm text-slate-500">No description provided for this incident.</p>
                        @endif
                    </div>
                </div>

                {{-- Students Involved --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="text-sm font-semibold text-slate-700">Students Involved</h2>
                            <span class="text-xs text-slate-500">
                                {{ $incident->students->count() }} student{{ $incident->students->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-200">
                        @forelse($incident->students as $student)
                            @php
                                $fullName = collect([
                                    $student->last_name . ',',
                                    $student->first_name,
                                    $student->middle_name,
                                    $student->suffix
                                ])->filter()->implode(' ');

                                $involvement = $student->pivot->involvement_type ?? 'Not specified';
                            @endphp

                            <div class="p-6">
                                <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2 mb-2">
                                            <h3 class="text-base font-semibold text-slate-800">
                                                {{ $fullName }}
                                            </h3>

                                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                {{ $involvement }}
                                            </span>
                                        </div>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600">
                                            <div>
                                                <span class="font-medium text-slate-700">Student No:</span>
                                                {{ $student->student_number ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <span class="font-medium text-slate-700">Gender:</span>
                                                {{ $student->gender ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <span class="font-medium text-slate-700">Email:</span>
                                                {{ $student->email ?? 'N/A' }}
                                            </div>

                                            <div>
                                                <span class="font-medium text-slate-700">Phone:</span>
                                                {{ $student->phone_number ?? 'N/A' }}
                                            </div>
                                        </div>

                                        @if($student->pivot->notes)
                                            <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                                    Involvement Notes
                                                </p>
                                                <p class="text-sm text-slate-700 whitespace-pre-line">
                                                    {{ $student->pivot->notes }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="px-6 py-12 text-center">
                                <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5V4H2v16h5m10 0v-4a4 4 0 00-8 0v4m8 0H9" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-700">No students linked</h3>
                                <p class="text-sm text-slate-500 mt-2">
                                    No student records are associated with this incident yet.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>

                {{-- Case Timeline / Incident Updates --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="text-sm font-semibold text-slate-700">Case Timeline</h2>
                            <span class="text-xs text-slate-500">
                                {{ $incident->incident_updates->count() }} update{{ $incident->incident_updates->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @forelse($incident->incident_updates as $index => $update)
                            @php
                                $updateStatus = $update->incident_status?->status_name ?? 'Unknown Status';
                                $updater = $update->user;
                                $updaterStaff = $updater?->staff;

                                $updaterName = $updater
                                    ? trim($updater->first_name . ' ' . $updater->last_name)
                                    : 'Unknown Staff';

                                $timelineBadge = match ($updateStatus) {
                                    'Pending' => 'bg-red-100 text-red-700',
                                    'Under Investigation' => 'bg-orange-100 text-orange-700',
                                    'Scheduled' => 'bg-blue-100 text-blue-700',
                                    'Resolved' => 'bg-green-100 text-green-700',
                                    'Unresolved' => 'bg-rose-100 text-rose-700',
                                    'Cancelled' => 'bg-slate-200 text-slate-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <div class="relative pl-8 {{ !$loop->last ? 'pb-8' : '' }}">
                                {{-- Timeline line --}}
                                @if(!$loop->last)
                                    <div class="absolute left-[11px] top-6 bottom-0 w-0.5 bg-slate-200"></div>
                                @endif

                                {{-- Timeline dot --}}
                                <div class="absolute left-0 top-1 w-6 h-6 rounded-full bg-slate-900 border-4 border-white shadow-sm"></div>

                                <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                                    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-3">
                                        <div>
                                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $timelineBadge }}">
                                                    {{ $updateStatus }}
                                                </span>

                                                @if($index === 0)
                                                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900 text-white">
                                                        Latest
                                                    </span>
                                                @endif
                                            </div>

                                            <h3 class="text-sm font-semibold text-slate-800">
                                                Updated by {{ $updaterName }}
                                            </h3>

                                            <p class="text-xs text-slate-500 mt-1">
                                                @if($updaterStaff)
                                                    Staff No: {{ $updaterStaff->staff_number ?? 'N/A' }}
                                                    @if($updaterStaff->department)
                                                        • {{ $updaterStaff->department }}
                                                    @endif
                                                @else
                                                    Staff record not found
                                                @endif
                                            </p>
                                        </div>

                                        <div class="text-xs text-slate-500 md:text-right shrink-0">
                                            <p class="font-medium text-slate-700">
                                                {{ $update->created_at->format('M d, Y') }}
                                            </p>
                                            <p>{{ $update->created_at->format('h:i A') }}</p>
                                        </div>
                                    </div>

                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                            Review Note
                                        </p>

                                        @if($update->note)
                                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                                {{ $update->note }}
                                            </p>
                                        @else
                                            <p class="text-sm text-slate-500 italic">
                                                No note provided for this update.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-10">
                                <div class="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-slate-700">No incident updates yet</h3>
                                <p class="text-sm text-slate-500 mt-2">
                                    This case has not been reviewed or updated yet.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>

            {{-- Right Sidebar --}}
            <aside class="lg:col-span-4 space-y-6">
                {{-- Quick Summary --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Quick Summary</h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Current Status</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $badgeClasses }}">
                                {{ $status }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Urgency</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium uppercase {{ $urgencyClasses }}">
                                {{ $incident->urgency_level ?? 'Low' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Category</span>
                            <span class="text-sm font-medium text-slate-700 text-right">
                                {{ $incident->category?->category_name ?? 'N/A' }}
                            </span>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Students Involved</span>
                            <span class="text-sm font-medium text-slate-700">
                                {{ $incident->students->count() }}
                            </span>
                        </div>

                        <div class="pt-4 border-t border-slate-200">
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                Latest Reviewed By
                            </p>

                            @if($latestReviewer)
                                <p class="text-sm font-medium text-slate-800">
                                    {{ $reviewerName }}
                                </p>
                                <p class="text-xs text-slate-500 mt-1">
                                    @if($latestReviewerStaff)
                                        {{ $latestReviewerStaff->staff_number ?? 'No Staff No.' }}
                                        @if($latestReviewerStaff->department)
                                            • {{ $latestReviewerStaff->department }}
                                        @endif
                                    @else
                                        Staff record not found
                                    @endif
                                </p>
                            @else
                                <p class="text-sm text-slate-500">No reviewer yet</p>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Metadata --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Record Metadata</h2>
                    </div>

                    <div class="p-6 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Incident ID</p>
                            <p class="text-slate-700 break-all">{{ $incident->id }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Created At</p>
                            <p class="text-slate-700">{{ $incident->created_at->format('M d, Y h:i A') }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Last Updated</p>
                            <p class="text-slate-700">
                                {{ $latestUpdate?->created_at?->format('M d, Y h:i A') ?? $incident->updated_at->format('M d, Y h:i A') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Total Updates</p>
                            <p class="text-slate-700">{{ $incident->incident_updates->count() }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
