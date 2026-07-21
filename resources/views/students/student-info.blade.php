@extends('layouts.app')

@section('title', 'Student Info - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Back --}}
    <div>
        <a href="{{ route('web.students.index') }}"
           class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Students List
        </a>
    </div>

    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden px-6 py-6">
        <h1 class="text-2xl font-bold text-slate-800">
            {{ $student->last_name }}, {{ $student->first_name }}
        </h1>
        <p class="text-sm text-slate-500 mt-1">Student No: {{ $student->student_number }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <section class="lg:col-span-8 space-y-6">
            {{-- Basic Info --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden p-6 space-y-3 text-sm">
                <p><span class="font-medium">Gender:</span> {{ $student->gender }}</p>
                <p><span class="font-medium">Birthdate:</span> {{ $student->birthdate }}</p>
                <p><span class="font-medium">Email:</span> {{ $student->email }}</p>
                <p><span class="font-medium">Phone:</span> {{ $student->phone_number }}</p>
                <p><span class="font-medium">Grade:</span> {{ $student->grade_sections?->grade_level }}</p>
                <p><span class="font-medium">Section:</span> {{ $student->grade_sections?->section }}</p>
                <p><span class="font-medium">School Year:</span> {{ $student->grade_sections?->school_year?->name ?? 'N/A' }}</p>
                <p><span class="font-medium">Status:</span> {{ $student->status }}</p>
            </div>

            {{-- Parent Relationships --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-700">Parent/Guardian Relationships</h2>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    @forelse($student->parent_guardians as $pg)
                        <p>{{ $pg->user->last_name }}, {{ $pg->user->first_name }} — {{ $pg->pivot->relationship }}</p>
                    @empty
                        <p class="text-slate-500">No parent/guardian linked.</p>
                    @endforelse
                </div>
            </div>

            {{-- Reports Involved --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Reports Involved</h2>
                    <span class="text-xs text-slate-500">{{ $student->reports->count() }} total</span>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    @forelse($student->reports as $report)
                        <div class="border rounded-lg p-3">
                            <p class="font-medium text-slate-800">{{ $report->incident_title ?? 'Incident' }}</p>
                            <p class="text-slate-600 text-xs">
                                Category: {{ $report->category?->category_name ?? 'N/A' }} •
                                Involvement: {{ $report->pivot->involvement_type ?? 'N/A' }}
                            </p>
                            @if($report->pivot->notes)
                                <p class="text-slate-500 text-xs mt-1">Notes: {{ $report->pivot->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">No reports linked.</p>
                    @endforelse
                </div>
            </div>

            {{-- Disciplinary Actions --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50 flex justify-between">
                    <h2 class="text-sm font-semibold text-slate-700">Disciplinary Actions</h2>
                    <span class="text-xs text-slate-500">{{ $student->disciplinary_actions->count() }} total</span>
                </div>
                <div class="p-6 space-y-3 text-sm">
                    @forelse($student->disciplinary_actions as $action)
                        <div class="border rounded-lg p-3">
                            <p class="font-medium text-slate-800">{{ $action->discipline_action }}</p>
                            <p class="text-slate-600 text-xs">
                                Imposed By: {{ $action->staff?->user?->last_name }}, {{ $action->staff?->user?->first_name }}
                            </p>
                            @if($action->notes)
                                <p class="text-slate-500 text-xs mt-1">Notes: {{ $action->notes }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-slate-500">No disciplinary actions recorded.</p>
                    @endforelse
                </div>
            </div>
        </section>

        {{-- Right Sidebar --}}
        <aside class="lg:col-span-4 space-y-6 lg:sticky lg:top-6 self-start">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                    <h2 class="text-sm font-semibold text-slate-700">Record Metadata</h2>
                </div>
                <div class="p-6 space-y-4 text-sm">
                    <p><span class="font-medium">Student ID:</span> {{ $student->id }}</p>
                    <p><span class="font-medium">Registered At:</span> {{ $student->created_at->format('M d, Y h:i A') }}</p>
                    <p><span class="font-medium">Last Updated:</span> {{ $student->updated_at->format('M d, Y h:i A') }}</p>
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
