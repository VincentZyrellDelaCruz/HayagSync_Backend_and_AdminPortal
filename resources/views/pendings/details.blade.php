@extends('layouts.app')

@section('title', 'Pending Registration Details - HayagSync')

@section('content')
    <div x-data="{ openModal: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Back --}}
        <div class="mb-6">
            <a href="{{ route('web.pendings.index') }}"
               class="inline-flex items-center gap-2 text-sm font-medium text-slate-600 hover:text-slate-900 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Pending Inbox
            </a>
        </div>

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
            <div class="px-6 py-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-slate-800">
                        {{ $pending->last_name }}, {{ $pending->first_name }}
                    </h1>
                    <p class="text-sm text-slate-500 mt-2">
                        Detailed pending registration overview and submitted proofs.
                    </p>
                </div>

                <div class="text-sm text-slate-500 lg:text-right shrink-0">
                    <p class="font-medium text-slate-700">
                        {{ $pending->created_at->format('M d, Y') }}
                    </p>
                    <p>{{ $pending->created_at->format('h:i A') }}</p>

                    {{-- Status Update Modal --}}
                    <button
                        class="mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        @click="openModal = true">
                        Update Status
                    </button>

                    <div x-show="openModal" x-cloak
                        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50">
                        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
                            <button class="absolute top-2 right-2 text-gray-600"
                                    @click="openModal = false">&times;</button>

                            <h2 class="text-lg font-semibold mb-4">Update Status</h2>

                            <form action="{{ route('web.pendings.update', $pending->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="student_id" value="{{ $pending->student_id }}" />
                                <input type="hidden" name="relationship" value="{{ $pending->relationship }}" />

                                <select name="status" class="w-full border rounded px-3 py-2 mb-4">
                                    <option value=""></option>
                                    <option value="Approved">Approved</option>
                                    <option value="Rejected">Rejected</option>
                                </select>

                                <button type="submit"
                                    class="px-4 py-2 bg-blue-600 text-white rounded">
                                    Update and Submit
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            {{-- Main Content --}}
            <section class="lg:col-span-8 space-y-6">

                {{-- Proof Images --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Submitted Proofs</h2>
                    </div>

                    <div class="p-6">
                        @if($pending->proofs->isNotEmpty())
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                @foreach ($pending->proofs as $proof)
                                    <div class="cursor-pointer rounded overflow-hidden shadow hover:opacity-80"
                                         x-data
                                         @click="$dispatch('open-modal', {
                                            type: 'image',
                                            src: '{{ asset('storage/' . $proof->file_path) }}',
                                            mime: '{{ $proof->mime_type }}'
                                         })">
                                        <img src="{{ asset('storage/' . $proof->file_path) }}"
                                             alt="{{ $proof->proof_type }}"
                                             class="w-full h-40 object-cover">
                                        <p class="text-xs text-center mt-2 text-slate-600">{{ $proof->proof_type }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No proof images submitted for this registration.</p>
                        @endif
                    </div>
                </div>

                {{-- Registration Overview --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Registration Overview</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Email</p>
                            <p class="text-sm text-slate-800">{{ $pending->email }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Phone Number</p>
                            <p class="text-sm text-slate-800">{{ $pending->phone_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Birthdate</p>
                            <p class="text-sm text-slate-800">{{ $pending->birthdate }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Occupation</p>
                            <p class="text-sm text-slate-800">{{ $pending->occupation }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Relationship</p>
                            <p class="text-sm text-slate-800">{{ $pending->relationship }}</p>
                        </div>
                    </div>
                </div>

            </section>

            {{-- Right Sidebar --}}
            <aside class="lg:col-span-4 space-y-6">
                {{-- Quick Summary --}}
                {{-- <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Quick Summary</h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Status</span>
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                {{ $pending->status }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-slate-500">Student ID</span>
                            <span class="text-sm font-medium text-slate-700 text-right">
                                {{ $pending->student_id }}
                            </span>
                        </div>
                    </div>
                </div> --}}

                {{-- Metadata --}}
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                        <h2 class="text-sm font-semibold text-slate-700">Related Child</h2>
                    </div>

                    <div class="p-6 space-y-4 text-sm">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Student Name</p>
                            <p class="text-slate-700 break-all">{{ $pending->student->last_name . ', ' . $pending->student->first_name }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Student Number</p>
                            <p class="text-slate-700">{{ $pending->student->student_number }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Relationship</p>
                            <p class="text-slate-700">{{ $pending->relationship }}</p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div x-data="{ open: false, type: '', src: '', mime: '' }"
        @open-modal.window="open = true; type = $event.detail.type; src = $event.detail.src; mime = $event.detail.mime"
        x-show="open"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-70 z-50"
        style="display:none;">
        <div class="bg-white rounded-lg shadow-lg max-w-3xl w-full p-4 relative">
            <button class="absolute top-2 right-2 text-gray-600" @click="open = false">&times;</button>

            <template x-if="type === 'image'">
                <img :src="src" alt="Evidence" class="w-full max-h-[80vh] object-contain">
            </template>

            <template x-if="type === 'video'">
                <video :src="src" :type="mime" controls autoplay class="w-full max-h-[80vh]"></video>
            </template>
        </div>
    </div>
@endsection
