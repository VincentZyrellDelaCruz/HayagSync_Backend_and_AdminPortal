@extends('layouts.app')

@section('title', 'Pending Registrations - HayagSync')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="flex items-center justify-between flex-wrap gap-3 mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Pending Registration List</h1>
                <p class="text-sm text-slate-500 mt-1">
                    Review and verify pending registrations with submitted proofs.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Sidebar --}}
            <aside class="lg:col-span-3">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6">
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Actions</h2>
                        <p class="text-xs text-slate-500 mt-1">Quick links</p>
                    </div>

                    <div class="p-5 space-y-6">
                        <div>
                            <a href="{{ route('web.pendings.index') }}"
                               class="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                                Refresh List
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            {{-- Pending List --}}
            <section class="lg:col-span-9">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    {{-- Top Bar --}}
                    <div class="px-5 py-4 border-b border-slate-200 bg-slate-50">
                        <div class="flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <h2 class="text-sm font-semibold text-slate-700">
                                    Pending Registrations
                                </h2>
                                <p class="text-xs text-slate-500 mt-1">
                                    {{ $pendings->count() }} total registration{{ $pendings->count() !== 1 ? 's' : '' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- List --}}
                    <div class="divide-y divide-slate-200">
                        @forelse($pendings as $pending)
                            <a href="{{ route('web.pendings.show', $pending->id) }}"
                               class="block px-5 py-4 hover:bg-slate-50 transition duration-150">
                                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                    {{-- Left Section --}}
                                    <div class="flex items-start gap-4 min-w-0 flex-1">
                                        <div class="min-w-0 flex-1">
                                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                                <h3 class="text-sm sm:text-base truncate text-slate-800">
                                                    {{ $pending->last_name }}, {{ $pending->first_name }}
                                                </h3>
                                                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                    Pending
                                                </span>
                                            </div>

                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                                <span><span class="font-medium text-slate-600">Email:</span> {{ $pending->email }}</span>
                                                <span><span class="font-medium text-slate-600">Occupation:</span> {{ $pending->occupation }}</span>
                                                <span><span class="font-medium text-slate-600">Parent/Guardian of:</span> {{ $pending->student->last_name . ', ' . $pending->student->first_name }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Right Section --}}
                                    <div class="flex lg:flex-col items-start lg:items-end justify-between gap-2 shrink-0 text-sm">
                                        <div class="text-slate-700 font-medium">
                                            {{ $pending->created_at->format('M d, Y') }}
                                        </div>
                                        <div class="text-xs text-slate-500">
                                            {{ $pending->created_at->format('h:i A') }}
                                        </div>
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
                                <h3 class="text-lg font-semibold text-slate-700">No pending registrations found</h3>
                                <p class="text-sm text-slate-500 mt-2">
                                    No applications available.
                                </p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
