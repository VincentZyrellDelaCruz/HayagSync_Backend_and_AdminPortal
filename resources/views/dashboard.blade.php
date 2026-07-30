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
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
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
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Reported Incidents This Month</p>
            <p class="text-3xl font-bold text-slate-800">{{ $monthlyReported }}</p>
        </div>
    </div>

    {{-- Academic Year Metric --}}
    <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-sm text-slate-500">Reported Incidents This Academic Year</p>
            <p class="text-3xl font-bold text-slate-800">{{ $yearlyReported }}</p>
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

    {{-- Latest AI Analysis --}}
    @if($latestAnalysis)
    <div class="bg-white p-6 rounded shadow">
        <h2 class="text-lg font-semibold">
            Latest AI Analysis ({{ ucfirst($latestAnalysis->periodicity) }}: {{ $latestAnalysis->period_label }})
        </h2>
        <div class="prose max-w-none text-sm text-gray-700 mt-3">
            {!! Str::markdown($latestAnalysis->output) !!}
        </div>
    </div>
    @else
    <p class="text-slate-500">No AI analysis available yet.</p>
    @endif

    {{-- Archived Analyses --}}
    <div class="bg-white p-6 rounded shadow mt-6">
        <h2 class="text-lg font-semibold">Archived AI Analyses</h2>

        {{-- Filters --}}
        <div class="flex space-x-4 mb-4">
            <a href="?filter=weekly" class="text-blue-600 hover:underline">Weekly</a>
            <a href="?filter=monthly" class="text-blue-600 hover:underline">Monthly</a>
            <a href="?filter=yearly" class="text-blue-600 hover:underline">Academic Year</a>
        </div>

        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-slate-700">Periodicity</th>
                    <th class="px-4 py-2 text-left font-semibold text-slate-700">Period</th>
                    <th class="px-4 py-2 text-left font-semibold text-slate-700">Created At</th>
                    <th class="px-4 py-2 text-left font-semibold text-slate-700">View</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @forelse($analyses as $analysis)
                    <tr>
                        <td class="px-4 py-2">{{ ucfirst($analysis->periodicity) }}</td>
                        <td class="px-4 py-2">{{ $analysis->period_label }}</td>
                        <td class="px-4 py-2">{{ $analysis->created_at->format('M d, Y H:i') }}</td>
                        <td class="px-4 py-2">
                            <button class="text-blue-600 hover:underline"
                                    onclick="showAnalysisModal('{{ $analysis->id }}')">
                                View
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-2 text-slate-500">No archived analyses yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{ $analyses->links() }}
    </div>
</div>

{{-- Modal --}}
<div id="analysisModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full p-6">
        <h3 class="text-lg font-semibold mb-4">AI Analysis Output</h3>
        <div id="analysisContent" class="prose max-w-none text-sm text-gray-700"></div>
        <button onclick="closeAnalysisModal()" class="mt-4 px-4 py-2 bg-blue-600 text-white rounded">Close</button>
    </div>
</div>

<script>
function showAnalysisModal(id) {
    fetch(`/ai-analysis/${id}`)
        .then(res => res.json())
        .then(data => {
            document.getElementById('analysisContent').innerHTML = data.output_html;
            document.getElementById('analysisModal').classList.remove('hidden');
        });
}
function closeAnalysisModal() {
    document.getElementById('analysisModal').classList.add('hidden');
}
</script>
@endsection
