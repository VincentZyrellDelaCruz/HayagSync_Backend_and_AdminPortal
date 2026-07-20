@extends('layouts.app')

@section('title', 'User Info - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Back --}}
    <div>
        <a href="{{ route('web.users.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to User Directory
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-6 py-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    {{ $user->last_name }}, {{ $user->first_name }}
                </h1>
                <p class="text-sm text-slate-500 mt-1">
                    {{ $user->email }} • {{ $user->phone_number ?? 'No phone' }}
                </p>
            </div>

            <div class="text-sm text-slate-500 lg:text-right shrink-0">
                <p class="font-medium text-slate-700">Registered: {{ $user->created_at->format('M d, Y h:i A') }}</p>
                <p>Last accessed: {{ $user->updated_at->format('M d, Y h:i A') }}</p>
            </div>
        </div>
    </div>

    {{-- Main Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-8 space-y-6">
            {{-- Staff Info --}}
            @if($user->staff)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Staff Information</h2>
                    </div>
                    <div class="p-6 space-y-3 text-sm text-slate-700">
                        <p><span class="font-medium">Staff No:</span> {{ $user->staff->staff_number }}</p>
                        <p><span class="font-medium">Gender:</span> {{ $user->gender }}</p>
                        <p><span class="font-medium">Birthdate:</span> {{ $user->birthdate }}</p>
                        <p><span class="font-medium">Department:</span> {{ $user->staff->department ?? 'N/A' }}</p>
                        <p><span class="font-medium">Admin:</span>
                            @if($user->staff->is_admin)
                                <span class="px-2 py-1 rounded bg-purple-100 text-purple-700 text-xs font-semibold">Yes</span>
                            @else
                                No
                            @endif
                        </p>
                    </div>
                </div>

                {{-- Position Timeline --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Position Timeline</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        @forelse($user->staff->positions->sortByDesc('pivot.assigned_at') as $position)
                            <div class="flex justify-between text-sm">
                                <span>{{ $position->position_name }}</span>
                                <span class="text-slate-500">{{ \Carbon\Carbon::parse($position->pivot->assigned_at)->format('M d, Y') }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No positions assigned.</p>
                        @endforelse
                    </div>
                </div>

                {{-- Adviser Sections --}}
                @if($user->staff->section_advisers->isNotEmpty())
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                            <h2 class="text-sm font-semibold text-slate-700">Adviser Sections</h2>
                        </div>
                        <div class="p-6 space-y-4">
                            @foreach($user->staff->section_advisers as $section)
                                <div class="flex justify-between text-sm">
                                    <span>{{ $section->grade_level }} - {{ $section->section }}</span>
                                    <span class="text-slate-500">{{ $section->school_year?->name ?? 'N/A' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- Parent/Guardian Info --}}
            @if($user->parent_guardian)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Parent/Guardian Information</h2>
                    </div>
                    <div class="p-6 space-y-3 text-sm text-slate-700">
                        <p><span class="font-medium">Parent Code:</span> {{ $user->parent_guardian->parent_code }}</p>
                        <p><span class="font-medium">Occupation:</span> {{ $user->parent_guardian->occupation }}</p>
                        <p><span class="font-medium">Gender:</span> {{ $user->gender }}</p>
                        <p><span class="font-medium">Birthdate:</span> {{ $user->birthdate }}</p>
                    </div>
                </div>

                {{-- Linked Students --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Linked Students</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        @forelse($user->parent_guardian->students as $student)
                            <div class="flex justify-between text-sm">
                                <span>{{ $student->last_name }}, {{ $student->first_name }}</span>
                                <span class="text-slate-500">{{ $student->pivot->relationship }}</span>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No linked students.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>

        {{-- Right Sidebar --}}
        <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-6 self-start">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-700">Record Metadata</h2>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <p><span class="font-medium">User ID:</span> {{ $user->id }}</p>
                    <p><span class="font-medium">Registered At:</span> {{ $user->created_at->format('M d, Y h:i A') }}</p>
                    <p><span class="font-medium">Last Updated:</span> {{ $user->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
