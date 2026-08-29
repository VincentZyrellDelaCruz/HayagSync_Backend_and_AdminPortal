import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Dashboard',
    href: '/dashboard',
  },
];

export default function Dashboard() {
  // Pull props passed from Laravel controller via Inertia
  const { weeklyReported, weeklyResolved, ongoingReports, monthlyReported, yearlyReported, topCategories, statusDistribution, latestAnalysis, analyses, auth } = usePage().props as any;

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Dashboard - HayagSync" />

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        {/* Header */}
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 px-6 py-6">
          <h1 className="text-2xl font-bold text-slate-800">Hello, {auth?.user?.first_name ?? 'Guest'}!</h1>
          <p className="text-sm text-slate-500 mt-1">Welcome back. Here’s an overview of the system metrics.</p>
        </div>

        {/* Top Metrics */}
        <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p className="text-sm text-slate-500">Reported Incidents This Week</p>
            <p className="text-3xl font-bold text-slate-800">{weeklyReported}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p className="text-sm text-slate-500">Resolved This Week</p>
            <p className="text-3xl font-bold text-green-600">{weeklyResolved}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p className="text-sm text-slate-500">Ongoing Reports</p>
            <p className="text-3xl font-bold text-orange-600">{ongoingReports}</p>
          </div>
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p className="text-sm text-slate-500">Reported Incidents This Month</p>
            <p className="text-3xl font-bold text-slate-800">{monthlyReported}</p>
          </div>
        </div>

        {/* Academic Year Metric */}
        <div className="grid grid-cols-1 gap-6">
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p className="text-sm text-slate-500">Reported Incidents This Academic Year</p>
            <p className="text-3xl font-bold text-slate-800">{yearlyReported}</p>
          </div>
        </div>

        {/* Bottom Panels */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
          {/* Top Categories */}
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50">
              <h2 className="text-sm font-semibold text-slate-700">Top Incident Categories This Academic Year</h2>
            </div>
            <div className="p-6 space-y-3 text-sm">
              {topCategories && Object.keys(topCategories).length > 0 ? (
                Object.entries(topCategories).map(([category, count]) => (
                  <div key={category} className="flex justify-between">
                    <span className="text-slate-700">{category}</span>
                    <span className="font-medium">{count as number}</span>
                  </div>
                ))
              ) : (
                <p className="text-slate-500">No categories reported yet.</p>
              )}
            </div>
          </div>

          {/* Incident Status */}
          <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50">
              <h2 className="text-sm font-semibold text-slate-700">Incident Status Distribution</h2>
            </div>
            <div className="p-6 space-y-3 text-sm">
              {statusDistribution &&
                Object.entries(statusDistribution).map(([status, percentage]) => (
                  <div key={status} className="flex items-center justify-between">
                    <span className="text-slate-700">{status}</span>
                    <span className="font-medium">{percentage}%</span>
                  </div>
                ))}
            </div>
          </div>
        </div>

        {/* Latest AI Analysis */}
        {latestAnalysis ? (
          <div className="bg-white p-6 rounded shadow">
            <h2 className="text-lg font-semibold">
              Latest AI Analysis ({latestAnalysis.periodicity}: {latestAnalysis.period_label})
            </h2>
            <div
              className="prose max-w-none text-sm text-gray-700 mt-3"
              dangerouslySetInnerHTML={{ __html: latestAnalysis.output_html }}
            />
          </div>
        ) : (
          <p className="text-slate-500">No AI analysis available yet.</p>
        )}

        {/* Archived Analyses */}
        <div className="bg-white p-6 rounded shadow mt-6">
          <h2 className="text-lg font-semibold">Archived AI Analyses</h2>

          {/* Filters */}
          <div className="flex space-x-4 mb-4">
            <a href="?filter=weekly" className="text-blue-600 hover:underline">Weekly</a>
            <a href="?filter=monthly" className="text-blue-600 hover:underline">Monthly</a>
            <a href="?filter=yearly" className="text-blue-600 hover:underline">Academic Year</a>
          </div>

          <table className="min-w-full divide-y divide-slate-200 text-sm">
            <thead className="bg-slate-50">
              <tr>
                <th className="px-4 py-2 text-left font-semibold text-slate-700">Periodicity</th>
                <th className="px-4 py-2 text-left font-semibold text-slate-700">Period</th>
                <th className="px-4 py-2 text-left font-semibold text-slate-700">Created At</th>
                <th className="px-4 py-2 text-left font-semibold text-slate-700">View</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-200">
              {analyses && analyses.length > 0 ? (
                analyses.map((analysis: any) => (
                  <tr key={analysis.id}>
                    <td className="px-4 py-2">{analysis.periodicity}</td>
                    <td className="px-4 py-2">{analysis.period_label}</td>
                    <td className="px-4 py-2">{analysis.created_at}</td>
                    <td className="px-4 py-2">
                      <button
                        className="text-blue-600 hover:underline"
                        onClick={() => window.showAnalysisModal(analysis.id)}
                      >
                        View
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr>
                  <td colSpan={4} className="px-4 py-2 text-slate-500">No archived analyses yet.</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </div>
    </AppLayout>
  );
}
