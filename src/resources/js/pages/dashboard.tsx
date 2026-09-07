import Pagination from '@/components/ui/pagination';
import Modal from '@/components/ui/modal';
import AppLayout from '@/layouts/app-layout';
import { AiAnalysis, DashboardProps, SharedData, type BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import {
    AlertTriangle, ArrowDownRight, ArrowUpRight, CalendarDays, CheckCircle2, Clock3,
    FileBarChart, FileText, Lightbulb, LoaderCircle, ShieldAlert, Target, TrendingUp, UsersRound, X,
} from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const statusClasses: Record<string, string> = {
    Pending: 'bg-amber-500',
    'Under Investigation': 'bg-orange-500',
    Scheduled: 'bg-blue-500',
    Escalated: 'bg-purple-500',
    Resolved: 'bg-emerald-500',
    Dismissed: 'bg-rose-500',
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });

const formatChange = (value: number | null) => {
    if (value === null) return null;
    return `${value > 0 ? '+' : ''}${value}%`;
};

const getAnalysisOutput = (analysis: AiAnalysis): string => {
    if (analysis.output?.trim()) return analysis.output.trim();

    if (analysis.output_html?.trim()) {
        return analysis.output_html
            .replace(/<br\s*\/?>/gi, '\n')
            .replace(/<\/p>/gi, '\n\n')
            .replace(/<[^>]*>/g, '')
            .replace(/&nbsp;/gi, ' ')
            .replace(/&amp;/gi, '&')
            .replace(/&lt;/gi, '<')
            .replace(/&gt;/gi, '>')
            .replace(/&quot;/gi, '"')
            .replace(/&#39;/gi, "'")
            .trim();
    }

    return '';
};

const isSimulatedAnalysis = (analysis: AiAnalysis): boolean =>
    getAnalysisOutput(analysis).startsWith('[SIMULATED AI ANALYSIS]');

const cleanAnalysisOutput = (analysis: AiAnalysis): string =>
    getAnalysisOutput(analysis).replace(/^\[SIMULATED AI ANALYSIS\]\s*/i, '').trim();

export default function Dashboard() {
    const { dashboardMetrics, topCategories, statusDistribution, latestAnalysis, analyses, analysisFilter } =
        usePage<SharedData & DashboardProps>().props;

    const [selectedAnalysis, setSelectedAnalysis] = useState<AiAnalysis | null>(null);
    const [isFilteringAnalysis, setIsFilteringAnalysis] = useState(false);
    const metrics = dashboardMetrics;
    const user = usePage<SharedData>().props.auth?.user;

    const handleAnalysisFilter = (value: string) => {
        if (value === analysisFilter) return;

        setIsFilteringAnalysis(true);

        router.get(route('dashboard'), value === 'all' ? {} : { filter: value }, {
            only: ['analyses', 'analysisFilter'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onFinish: () => setIsFilteringAnalysis(false),
        });
    };

    const analysisFilters = [
        ['all', 'All'],
        ['weekly', 'Weekly'],
        ['monthly', 'Monthly'],
        ['yearly', 'Academic Year'],
    ] as const;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard - HayagSync" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    {/* Header */}
                    <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div className="relative px-5 py-6 sm:px-7 sm:py-7">
                            <div className="absolute right-0 top-0 h-32 w-32 rounded-full bg-blue-50 blur-3xl" />

                            <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <h1 className="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                        Welcome back, {user?.first_name ?? 'User'}
                                    </h1>

                                    <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                        Monitor incident activity, case progress, and AI-generated insights across HayagSync.
                                    </p>
                                </div>

                                <div className="flex shrink-0 items-center gap-2 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                    <CalendarDays className="h-5 w-5 text-slate-500" />

                                    <div>
                                        <p className="text-[11px] font-medium uppercase tracking-wide text-slate-400">Academic Year</p>
                                        <p className="text-sm font-semibold text-slate-800">{metrics.academic_year_label}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Primary Metrics */}
                    <section className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <MetricCard label="Reported This Academic Year" value={metrics.academic_year_reported} description="Total incidents filed" icon={FileText} />
                        <MetricCard label="Ongoing Cases" value={metrics.ongoing_reports} description="Pending to escalated" icon={Clock3} valueClassName="text-amber-600" />
                        <MetricCard label="Resolved Cases" value={metrics.resolved_reports} description="Cases reported this academic year" icon={CheckCircle2} valueClassName="text-emerald-600" />
                        <MetricCard label="Resolution Rate" value={`${metrics.resolution_rate}%`} description="Academic-year reported cases" icon={Target} valueClassName="text-blue-600" />
                    </section>

                    {/* Recent Activity */}
                    <section className="grid grid-cols-1 gap-3 md:grid-cols-3">
                        <ActivityCard label="Reported This Week" value={metrics.weekly_reported} change={metrics.weekly_change} description="Compared with last week" icon={TrendingUp} />
                        <ActivityCard label="Resolved This Week" value={metrics.weekly_resolved} description="Based on latest case update" icon={CheckCircle2} iconClassName="bg-emerald-50 text-emerald-600" />
                        <ActivityCard label="Reported This Month" value={metrics.monthly_reported} change={metrics.monthly_change} description="Compared with last month" icon={CalendarDays} />
                    </section>

                    {/* Analytics */}
                    <section className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        {/* Category Analysis */}
                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-7">
                            <div className="flex items-center justify-between gap-3 border-b border-slate-200 px-5 py-4">
                                <div>
                                    <div className="flex items-center gap-2">
                                        <FileBarChart className="h-4 w-4 text-slate-500" />
                                        <h2 className="text-sm font-semibold text-slate-800">Top Incident Categories</h2>
                                    </div>

                                    <p className="mt-1 text-xs text-slate-400">Academic year {metrics.academic_year_label}</p>
                                </div>
                            </div>

                            <div className="space-y-5 p-5">
                                {topCategories.length > 0 ? (
                                    topCategories.map((category, index) => (
                                        <div key={category.category_name}>
                                            <div className="mb-2 flex items-center justify-between gap-3">
                                                <div className="flex min-w-0 items-center gap-2">
                                                    <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-[11px] font-bold text-slate-500">
                                                        {index + 1}
                                                    </span>

                                                    <span className="truncate text-sm font-medium text-slate-700">{category.category_name}</span>
                                                </div>

                                                <span className="shrink-0 text-xs font-semibold text-slate-600">{category.total} · {category.percentage}%</span>
                                            </div>

                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div className="h-full rounded-full bg-blue-500 transition-all" style={{ width: `${Math.min(category.percentage, 100)}%` }} />
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <EmptyState text="No incident categories reported yet." />
                                )}
                            </div>
                        </div>

                        {/* Status Distribution */}
                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-5">
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-2">
                                    <ShieldAlert className="h-4 w-4 text-slate-500" />
                                    <h2 className="text-sm font-semibold text-slate-800">Case Status Distribution</h2>
                                </div>

                                <p className="mt-1 text-xs text-slate-400">Current status of academic-year reports</p>
                            </div>

                            <div className="space-y-4 p-5">
                                {statusDistribution.length > 0 ? (
                                    statusDistribution.map((status) => (
                                        <div key={status.status_name}>
                                            <div className="mb-2 flex items-center justify-between gap-3">
                                                <div className="flex min-w-0 items-center gap-2">
                                                    <span className={`h-2.5 w-2.5 shrink-0 rounded-full ${statusClasses[status.status_name] ?? 'bg-slate-300'}`} />
                                                    <span className="truncate text-sm font-medium text-slate-700">{status.status_name}</span>
                                                </div>

                                                <span className="text-xs font-semibold text-slate-600">{status.total} · {status.percentage}%</span>
                                            </div>

                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div className={`h-full rounded-full ${statusClasses[status.status_name] ?? 'bg-slate-400'}`} style={{ width: `${Math.min(status.percentage, 100)}%` }} />
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <EmptyState text="No status data available yet." />
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Case Health */}
                    <section className="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div className="rounded-2xl border border-blue-100 bg-blue-50/70 p-5">
                            <div className="flex items-start gap-3">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                                    <TrendingUp className="h-5 w-5" />
                                </div>

                                <div className="min-w-0">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">Resolution Performance</p>
                                    <p className="mt-1 text-xl font-bold text-slate-900">{metrics.resolution_rate}%</p>
                                    <p className="mt-1 text-xs leading-5 text-slate-500">
                                        Percentage of incidents reported this academic year that are currently resolved.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-amber-100 bg-amber-50/70 p-5">
                            <div className="flex items-start gap-3">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-600 shadow-sm">
                                    <AlertTriangle className="h-5 w-5" />
                                </div>

                                <div className="min-w-0">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-amber-600">Active Case Load</p>
                                    <p className="mt-1 text-xl font-bold text-slate-900">{metrics.ongoing_reports}</p>
                                    <p className="mt-1 text-xs leading-5 text-slate-500">
                                        Reports that still require attention, investigation, scheduling, or escalation.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    {/* Latest AI Analysis */}
                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 bg-linear-to-r from-slate-50 to-white px-5 py-5 sm:px-6">
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-start gap-3">
                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                                        <Lightbulb className="h-5 w-5" />
                                    </div>

                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-xs font-semibold uppercase tracking-wide text-purple-600">AI-Assisted Analysis</p>

                                            {latestAnalysis && isSimulatedAnalysis(latestAnalysis) && (
                                                <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">
                                                    Simulation
                                                </span>
                                            )}
                                        </div>

                                        <h2 className="mt-1 text-base font-semibold text-slate-800">Latest Summary & Recommendations</h2>

                                        {latestAnalysis && (
                                            <div className="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-slate-400">
                                                <span>{latestAnalysis.periodicity}</span>
                                                <span>·</span>
                                                <span>{latestAnalysis.period_label}</span>
                                                <span>·</span>
                                                <span>Generated {formatDate(latestAnalysis.created_at)}</span>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="p-5 sm:p-6">
                            {latestAnalysis ? (
                                <div className="overflow-hidden rounded-2xl border border-slate-100 bg-slate-50/70">
                                    {cleanAnalysisOutput(latestAnalysis) ? (
                                        <>
                                            <div className="relative max-h-96 overflow-hidden">
                                                <div className="whitespace-pre-line p-5 text-sm leading-7 text-slate-700">
                                                    {cleanAnalysisOutput(latestAnalysis)}
                                                </div>

                                                <div className="pointer-events-none absolute inset-x-0 bottom-0 h-16 bg-linear-to-t from-slate-50/95 to-transparent" />
                                            </div>

                                            <div className="flex items-center justify-between gap-3 border-t border-slate-200 bg-white px-5 py-3">
                                                <p className="text-[11px] text-slate-400">
                                                    AI-assisted information is intended to support professional judgment.
                                                </p>

                                                <button type="button" onClick={() => setSelectedAnalysis(latestAnalysis)} className="shrink-0 text-xs font-semibold text-blue-600 transition hover:text-blue-700">
                                                    View full analysis →
                                                </button>
                                            </div>
                                        </>
                                    ) : (
                                        <div className="p-5">
                                            <EmptyState icon={Lightbulb} text="This analysis record exists, but no generated output is available." />

                                            <div className="mt-3 flex justify-center">
                                                <button type="button" onClick={() => setSelectedAnalysis(latestAnalysis)} className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200">
                                                    View record
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <EmptyState icon={Lightbulb} text="No AI analysis is available yet." />
                            )}
                        </div>
                    </section>

                    {/* Archived Analyses */}
                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex flex-col gap-4 border-b border-slate-200 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                            <div>
                                <div className="flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-slate-500" />
                                    <h2 className="text-sm font-semibold text-slate-800">Archived AI Analyses</h2>
                                </div>

                                <p className="mt-1 text-xs text-slate-400">Review previously generated summaries and recommendations.</p>
                            </div>

                            <div className="flex flex-wrap gap-1 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="AI analysis filters">
                                {analysisFilters.map(([value, label]) => {
                                    const active = analysisFilter === value;

                                    return (
                                        <button
                                            key={value}
                                            type="button"
                                            role="tab"
                                            aria-selected={active}
                                            disabled={isFilteringAnalysis}
                                            onClick={() => handleAnalysisFilter(value)}
                                            className={`inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium transition ${
                                                active ? 'bg-white text-slate-800 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                            } disabled:cursor-wait disabled:opacity-60`}
                                        >
                                            {isFilteringAnalysis && active && <LoaderCircle className="h-3 w-3 animate-spin" />}
                                            {label}
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Periodicity</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Period</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Created</th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Action</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {analyses.data.length > 0 ? (
                                        analyses.data.map((analysis) => (
                                            <tr key={analysis.id} className="transition hover:bg-slate-50">
                                                <td className="px-5 py-4">
                                                    <span className="rounded-full bg-purple-50 px-2.5 py-1 text-xs font-semibold capitalize text-purple-700">{analysis.periodicity}</span>
                                                </td>

                                                <td className="px-5 py-4 text-sm text-slate-700">
                                                    <div className="flex items-center gap-2">
                                                        <span>{analysis.period_label}</span>
                                                    </div>
                                                </td>

                                                <td className="px-5 py-4 text-sm text-slate-500">{formatDate(analysis.created_at)}</td>

                                                <td className="px-5 py-4 text-right">
                                                    <button type="button" onClick={() => setSelectedAnalysis(analysis)} className="rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200">
                                                        View
                                                    </button>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={4} className="px-5 py-12 text-center text-sm text-slate-500">No archived AI analyses found for this filter.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="divide-y divide-slate-100 md:hidden">
                            {analyses.data.length > 0 ? (
                                analyses.data.map((analysis) => (
                                    <button key={analysis.id} type="button" onClick={() => setSelectedAnalysis(analysis)} className="block w-full p-4 text-left transition hover:bg-slate-50">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="inline-flex rounded-full bg-purple-50 px-2.5 py-1 text-[11px] font-semibold capitalize text-purple-700">{analysis.periodicity}</span>

                                                    {isSimulatedAnalysis(analysis) && (
                                                        <span className="rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-semibold uppercase tracking-wide text-amber-700 ring-1 ring-amber-200">
                                                            Simulation
                                                        </span>
                                                    )}
                                                </div>

                                                <p className="mt-2 truncate text-sm font-semibold text-slate-800">{analysis.period_label}</p>
                                                <p className="mt-1 text-xs text-slate-400">{formatDate(analysis.created_at)}</p>
                                            </div>

                                            <span className="shrink-0 text-xs font-medium text-blue-600">View →</span>
                                        </div>
                                    </button>
                                ))
                            ) : (
                                <div className="px-5 py-12 text-center text-sm text-slate-500">No archived AI analyses found for this filter.</div>
                            )}
                        </div>

                        {analyses.links.length > 3 && (
                            <div className="border-t border-slate-200 px-4 py-4 sm:px-5">
                                <Pagination links={analyses.links} meta={{ from: analyses.from, to: analyses.to, total: analyses.total }} />
                            </div>
                        )}
                    </section>
                </div>
            </div>

            <Modal
                isOpen={!!selectedAnalysis}
                onClose={() => setSelectedAnalysis(null)}
                title={selectedAnalysis ? `${selectedAnalysis.periodicity} Analysis` : 'AI Analysis'}
                description={selectedAnalysis?.period_label ?? 'Analysis details'}
                size="lg"
            >
                {selectedAnalysis && (
                    <div className="space-y-4">
                        <div className="flex flex-col gap-3 rounded-xl bg-purple-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-purple-600">Analysis Period</p>
                                <p className="mt-0.5 text-sm font-semibold text-slate-800">{selectedAnalysis.period_label}</p>
                                <p className="mt-1 text-xs text-slate-500">Generated {formatDate(selectedAnalysis.created_at)}</p>
                            </div>

                            <div className="flex items-center gap-2">
                                {isSimulatedAnalysis(selectedAnalysis) && (
                                    <span className="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 ring-1 ring-amber-200">Simulation</span>
                                )}

                                <span className="rounded-full bg-white px-2.5 py-1 text-xs font-semibold capitalize text-purple-700 shadow-sm">{selectedAnalysis.periodicity}</span>
                            </div>
                        </div>

                        {cleanAnalysisOutput(selectedAnalysis) ? (
                            <div className="rounded-2xl border border-slate-200 bg-slate-50/70 p-5">
                                <div className="whitespace-pre-line text-sm leading-7 text-slate-700">{cleanAnalysisOutput(selectedAnalysis)}</div>
                            </div>
                        ) : (
                            <EmptyState icon={Lightbulb} text="No generated analysis output is available for this record." />
                        )}

                        <div className="rounded-xl border border-slate-200 bg-white px-4 py-3">
                            <p className="text-[11px] leading-5 text-slate-500">
                                This analysis is AI-assisted and is intended to support, not replace, professional judgment, established school policies, and formal investigations.
                            </p>
                        </div>
                    </div>
                )}
            </Modal>
        </AppLayout>
    );
}

function MetricCard({
    label, value, description, icon: Icon, valueClassName = 'text-slate-900',
}: {
    label: string; value: number | string; description: string; icon: typeof FileText; valueClassName?: string;
}) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="text-xs font-medium text-slate-500">{label}</p>
                    <p className={`mt-2 text-2xl font-bold tracking-tight ${valueClassName}`}>{value}</p>
                    <p className="mt-1 text-[11px] leading-4 text-slate-400">{description}</p>
                </div>

                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}

function ActivityCard({
    label, value, change, description, icon: Icon, iconClassName = 'bg-blue-50 text-blue-600',
}: {
    label: string; value: number; change?: number | null; description: string; icon: typeof TrendingUp; iconClassName?: string;
}) {
    const changeText = formatChange(change ?? null);

    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start gap-3">
                <div className={`flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ${iconClassName}`}>
                    <Icon className="h-5 w-5" />
                </div>

                <div className="min-w-0">
                    <p className="text-xs font-medium text-slate-500">{label}</p>

                    <div className="mt-1 flex items-center gap-2">
                        <p className="text-2xl font-bold tracking-tight text-slate-900">{value}</p>

                        {changeText && (
                            <span className={`inline-flex items-center gap-0.5 rounded-full px-2 py-0.5 text-[10px] font-semibold ${(change ?? 0) >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700'}`}>
                                {(change ?? 0) >= 0 ? <ArrowUpRight className="h-3 w-3" /> : <ArrowDownRight className="h-3 w-3" />}
                                {changeText}
                            </span>
                        )}
                    </div>

                    <p className="mt-1 text-[11px] text-slate-400">{description}</p>
                </div>
            </div>
        </div>
    );
}

function EmptyState({ text, icon: Icon = UsersRound }: { text: string; icon?: typeof UsersRound }) {
    return (
        <div className="flex flex-col items-center justify-center py-10 text-center">
            <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                <Icon className="h-5 w-5" />
            </div>

            <p className="text-sm text-slate-500">{text}</p>
        </div>
    );
}
