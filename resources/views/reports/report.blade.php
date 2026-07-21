@extends('layouts.app')

@section('title', 'Incident Details - HayagSync')

@section('content')
    @php
        $latestUpdate = $report->latest_update;
        $status = $latestUpdate?->report_status?->status_name ?? 'No Status';
        // $urgency = strtolower($report->urgency_level ?? 'low');

        $latestReviewer = $latestUpdate?->user;
        $latestReviewerStaff = $latestReviewer?->staff;

        $badgeClasses = match ($status) {
            'Pending' => 'bg-red-100 text-amber-700',
            'Under Investigation' => 'bg-orange-100 text-orange-700',
            'Scheduled' => 'bg-blue-100 text-blue-700',
            'Resolved' => 'bg-green-100 text-green-700',
            'Dropped' => 'bg-rose-100 text-rose-700',
            default => 'bg-gray-100 text-gray-700',
        };

        /* $urgencyClasses = match ($urgency) {
            'critical' => 'bg-red-100 text-red-700',
            'high' => 'bg-amber-100 text-amber-700',
            'medium' => 'bg-blue-100 text-blue-700',
            'low' => 'bg-slate-100 text-slate-700',
            default => 'bg-slate-100 text-slate-700',
        }; */

        $reporter = $report->user
            ? $report->user->last_name . ', ' . $report->user->first_name
            : 'Unknown Reporter';

        $reviewerName = $latestReviewer
            ? trim($latestReviewer->first_name . ' ' . $latestReviewer->last_name)
            : null;
    @endphp

    <div x-data="{ openModal: false }" class="space-y-8">
        {{-- Back --}}
        <div>
            <a href="{{ route('web.reports.index') }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Incident Inbox
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="px-3 py-1 rounded-full text-xs font-medium {{ $badgeClasses }}">
                            {{ $status }}
                        </span>
                        {{-- <span class="px-3 py-1 rounded-full text-xs font-medium uppercase {{ $urgencyClasses }}">
                            {{ $report->urgency_level ?? 'Low' }}
                        </span> --}}
                        @if(in_array($status, ['Pending', 'Under Investigation']))
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                Needs Attention
                            </span>
                        @endif
                    </div>
                    <h1 class="text-2xl font-bold text-slate-800">{{ $report->incident_title }}</h1>
                    <p class="text-sm text-slate-500 mt-2">Detailed incident case overview, involved students, and review timeline.</p>
                </div>

                <div class="text-sm text-slate-500 lg:text-right shrink-0">
                    <p class="font-medium text-slate-700">{{ $report->created_at->format('M d, Y') }}</p>
                    <p>{{ $report->created_at->format('h:i A') }}</p>

                    @if (!in_array($report->current_status->status_name, ['Cancelled', 'Resolved', 'Dismissed']))
                        <button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                                @click="openModal = true">
                            Schedule Coordination Meeting
                        </button>
                        <button class="mt-3 px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">
                            Forward to OSD Officer
                        </button>

                        {{-- Modal --}}
                        <div x-show="openModal" x-cloak
                            class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                            <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative text-left">
                                <button class="absolute top-2 right-2 text-gray-600"
                                        @click="openModal = false">&times;</button>

                                <h2 class="text-lg font-semibold mb-4">Schedule Meeting</h2>

                                <form action="{{ route('web.reports.update', $report->id) }}" method="POST" class="space-y-4 text-left">
                                    @csrf
                                    @method('PUT')

                                    <label for="meeting_datetime" class="block text-sm font-medium text-gray-700">
                                        Choose date and time:
                                    </label>
                                    <input type="datetime-local"
                                        id="meeting_datetime"
                                        name="meeting_datetime"
                                        class="w-full border rounded px-3 py-2">

                                    <label for="note" class="block text-sm font-medium text-gray-700">
                                        Note:
                                    </label>
                                    <textarea id="note" name="note" rows="3"
                                            class="w-full border rounded px-3 py-2"></textarea>

                                    <input type="hidden" name="status_id" value="3"/>
                                    <input type="hidden" name="type" value="schedule"/>

                                    <button type="submit"
                                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Submit
                                    </button>
                                </form>
                            </div>
                        </div>

                    @endif
                </div>
            </div>
        </div>

        {{-- Main Grid --}}
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
                            <p class="text-sm text-slate-800">{{ $report->category?->category_name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Incident Date & Time</p>
                            <p class="text-sm text-slate-800">
                                {{ $report->incident_date ? \Carbon\Carbon::parse($report->incident_date)->format('M d, Y') : 'Not specified' }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Location</p>
                            <p class="text-sm text-slate-800">{{ $report->location ?? 'Not specified' }}</p>
                        </div>
                    </div>
                </div>

                {{-- Evidences --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Evidences</h2>
                    </div>

                    <div class="p-6">
                        @if($report->report_evidences->isNotEmpty())
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach ($report->report_evidences as $evidence)
                                    <div
                                        class="cursor-pointer rounded overflow-hidden shadow hover:opacity-80"
                                        x-data
                                        @click="$dispatch('open-modal', {
                                            type: '{{ Str::startsWith($evidence->mime_type, 'image/') ? 'image' : 'video' }}',
                                            src: '{{ asset('storage/' . $evidence->file_path) }}',
                                            mime: '{{ $evidence->mime_type }}'
                                        })"
                                    >
                                        @if (Str::startsWith($evidence->mime_type, 'image/'))
                                            <img src="{{ asset('storage/' . $evidence->file_path) }}"
                                                alt="{{ $evidence->file_name }}"
                                                class="w-full h-40 object-cover">
                                        @elseif (Str::startsWith($evidence->mime_type, 'video/'))
                                            <video class="w-full h-40 object-cover" muted>
                                                <source src="{{ asset('storage/' . $evidence->file_path) }}" type="{{ $evidence->mime_type }}">
                                            </video>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No evidence (image/video) provided for this incident.</p>
                        @endif
                    </div>
                </div>

                {{-- Description --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Incident Description</h2>
                    </div>

                    <div class="p-6">
                        @if($report->description)
                            <p class="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                {{ $report->description }}
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
                                {{ $report->students->count() }} student{{ $report->students->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <div class="divide-y divide-slate-200">
                        @forelse($report->students as $student)
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

                {{-- Case Timeline --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <h2 class="text-sm font-semibold text-slate-700">Case Timeline</h2>
                            <span class="text-xs text-slate-500">
                                {{ $report->report_updates->count() }} update{{ $report->report_updates->count() !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>

                    <div class="p-6">
                        @forelse($report->report_updates as $index => $update)
                            @php
                                $updateStatus = $update->report_status?->status_name ?? 'Unknown Status';
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
            <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-6 self-start">
                {{-- Quick Summary --}}
                {{--
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
                 --}}
                {{-- Metadata --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Record Metadata</h2>
                    </div>

                    <div class="p-6 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Incident ID</p>
                            <p class="text-slate-700 break-all">{{ $report->id }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Created At</p>
                            <p class="text-slate-700">{{ $report->created_at->format('M d, Y h:i A') }}</p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Last Updated</p>
                            <p class="text-slate-700">
                                {{ $latestUpdate?->created_at?->format('M d, Y h:i A') ?? $report->updated_at->format('M d, Y h:i A') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Total Updates</p>
                            <p class="text-slate-700">{{ $report->report_updates->count() }}</p>
                        </div>
                    </div>
                </div>

                {{-- Meetings --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                        <h2 class="text-sm font-semibold text-slate-700">Meetings</h2>
                    </div>

                    <div class="p-6 space-y-4 text-sm">
                        @forelse($report->meetings as $meeting)
                            @php
                                $statusClasses = match ($meeting->status) {
                                    'Active'   => 'bg-green-100 text-green-700',
                                    'Canceled' => 'bg-red-100 text-red-700',
                                    'Finished' => 'bg-blue-100 text-blue-700',
                                    default    => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <div x-data="{ openChat: false }" class="border rounded-lg p-4 space-y-3">
                                {{-- Meeting Header --}}
                                <div class="flex items-center justify-between">
                                    <span class="font-medium text-slate-800">
                                        {{ $meeting->meeting_date->format('M d, Y h:i A') }}
                                    </span>
                                    <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $statusClasses }}">
                                        {{ $meeting->status }}
                                    </span>
                                </div>

                                <p class="text-slate-600">
                                    <span class="font-medium">Scheduled By:</span>
                                    {{ $meeting->scheduler?->last_name }}, {{ $meeting->scheduler?->first_name }}
                                </p>
                                @if($meeting->notes)
                                    <p class="text-slate-600">
                                        <span class="font-medium">Notes:</span> {{ $meeting->notes }}
                                    </p>
                                @endif

                                {{-- Action Buttons --}}
                                @if($meeting->status === 'Active')
                                    <div class="flex gap-2">
                                        <form action="{{ route('web.meetings.update', [$meeting->id, 'cancel']) }}" method="POST">
                                            @csrf @method('PUT')
                                            <button type="submit" class="px-3 py-1 bg-red-600 text-white rounded text-xs hover:bg-red-700">
                                                Cancel
                                            </button>
                                        </form>
                                        <form action="{{ route('web.meetings.update', [$meeting->id, 'finish']) }}" method="POST">
                                            @csrf @method('PUT')
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
                                                Finish
                                            </button>
                                        </form>
                                    </div>
                                @endif

                                {{-- Chat Box --}}
                                <div>
                                    <button @click="openChat = !openChat"
                                        class="mt-2 text-xs text-blue-600 hover:underline">
                                        {{ $meeting->status === 'Active' ? 'Open Chat' : 'View Chat History' }}
                                    </button>

                                    <div x-show="openChat" class="mt-3 border rounded-lg bg-slate-50 p-3 max-h-64 overflow-y-auto space-y-2">
                                        @forelse($meeting->chatMessages as $msg)
                                            <div class="flex {{ $msg->sender_id === Auth::id() ? 'justify-end' : 'justify-start' }}">
                                                <div class="max-w-xs px-3 py-2 rounded-lg text-sm
                                                    {{ $msg->sender_id === Auth::id() ? 'bg-blue-600 text-white' : 'bg-gray-200 text-slate-800' }}">
                                                    <p>{{ $msg->message }}</p>
                                                    <span class="block text-[10px] mt-1 opacity-70">
                                                        {{ $msg->created_at->format('h:i A') }}
                                                    </span>
                                                </div>
                                            </div>
                                        @empty
                                            <p class="text-xs text-slate-500">No messages yet.</p>
                                        @endforelse
                                    </div>

                                    {{-- Message Input (only if Active) --}}
                                    @if($meeting->status === 'Active')
                                        <form action="{{ route('web.chat_messages.store', $meeting->id) }}" method="POST" class="mt-2 flex gap-2">
                                            @csrf
                                            <input type="text" name="message" placeholder="Type a message..."
                                                class="flex-1 border rounded px-3 py-1 text-sm"
                                                required>
                                            <button type="submit" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700">
                                                Send
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-500">No meetings scheduled for this report.</p>
                        @endforelse
                    </div>
                </div>

            </aside>
        </div>
    </div>
@endsection
