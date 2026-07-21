@extends('layouts.app')

@section('title', 'Dashboard - HayagSync')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    {{-- Header --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-6">
        <h1 class="text-2xl font-bold text-slate-800">Hello, Admin!</h1>
        <p class="text-sm text-slate-500 mt-1">Welcome back. Here’s an overview of the system metrics.</p>
    </div>

    {{-- Top Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Reported Incidents This Week</p>
            <p class="text-3xl font-bold text-slate-800">{{ $weeklyReported }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Resolved This Week</p>
            <p class="text-3xl font-bold text-green-600">{{ $weeklyResolved }}</p>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Ongoing Reports</p>
            <p class="text-3xl font-bold text-orange-600">{{ $ongoingReports }}</p>
        </div>
    </div>

    {{-- Bottom Panels --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Top Categories --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-700">Top Incident Categories This Academic Year</h2>
            </div>
            <div class="p-6 space-y-3 text-sm">
                @forelse($topCategories as $category => $count)
                    <div class="flex justify-between">
                        <span class="text-slate-700">{{ $category }}</span>
                        <span class="font-medium">{{ $count }}</span>
                    </div>
                @empty
                    <p class="text-slate-500">No categories reported yet.</p>
                @endforelse
            </div>
        </div>

        {{-- Incident Status --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-sm font-semibold text-slate-700">Incident Status Distribution</h2>
            </div>
            <div class="p-6 space-y-3 text-sm">
                @foreach($statusDistribution as $status => $percentage)
                    <div class="flex items-center justify-between">
                        <span class="text-slate-700">{{ $status }}</span>
                        <span class="font-medium">{{ $percentage }}%</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
