import MetricCard from '@/components/ui/metric-card';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    CalendarDays,
    CheckCircle2,
    Clock3,
    FileText,
    GraduationCap,
    ShieldCheck,
    UserRound,
} from 'lucide-react';

interface ParentDashboardProps {
    dashboardMetrics: {
        related_students: number;
        submitted_reports: number;
        ongoing_cases: number;
        resolved_cases: number;
        resolution_rate: number;
    };
    relatedStudents: Array<{
        id: string;
        student_number: string;
        name: string;
        grade_level?: string | null;
        section?: string | null;
        status: string;
        case_count: number;
    }>;
    recentCases: Array<{
        id: string;
        report_code: string;
        incident_title: string;
        student_name: string;
        category_name: string;
        status_name: string;
        incident_date?: string | null;
        incident_time?: string | null;
        created_at?: string | null;
    }>;
    statusDistribution: Array<{
        status_name: string;
        total: number;
        percentage: number;
    }>;
}

const breadcrumbs = [{ title: 'Dashboard', href: '/dashboard' }];

const statusClasses: Record<string, string> = {
    Pending: 'bg-amber-500',
    'Under Investigation': 'bg-orange-500',
    Scheduled: 'bg-blue-500',
    Escalated: 'bg-purple-500',
    Resolved: 'bg-emerald-500',
    Dismissed: 'bg-rose-500',
};

const formatDate = (value?: string | null) =>
    value
        ? new Date(value).toLocaleDateString('en-US', {
              month: 'short',
              day: 'numeric',
              year: 'numeric',
          })
        : '—';

export default function ParentDashboard() {
    const { dashboardMetrics, relatedStudents, recentCases, statusDistribution } =
        usePage().props as unknown as ParentDashboardProps;

    const user = (usePage().props as any).auth?.user;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Parent Dashboard - HayagSync" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div className="relative px-5 py-6 sm:px-7 sm:py-7">
                            <div className="absolute right-0 top-0 h-32 w-32 rounded-full bg-blue-50 blur-3xl" />

                            <div className="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                <div className="min-w-0">
                                    <h1 className="mt-2 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                        Welcome back, {user?.first_name ?? 'Parent'}
                                    </h1>

                                    <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                        Stay informed about your related students and the progress of cases associated with them.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <MetricCard
                            label="Related Students"
                            value={dashboardMetrics.related_students}
                            icon={GraduationCap}
                        />

                        <MetricCard
                            label="Reports Submitted"
                            value={dashboardMetrics.submitted_reports}
                            icon={FileText}
                        />

                        <MetricCard
                            label="Ongoing Cases"
                            value={dashboardMetrics.ongoing_cases}
                            icon={Clock3}
                            valueClassName="text-amber-600"
                        />

                        <MetricCard
                            label="Resolved Cases"
                            value={dashboardMetrics.resolved_cases}
                            icon={CheckCircle2}
                            valueClassName="text-emerald-600"
                        />
                    </section>

                    <section className="grid grid-cols-1 gap-6 lg:grid-cols-12">

                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-7">
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-2">
                                    <GraduationCap className="h-4 w-4 text-slate-500" />
                                    <h2 className="text-sm font-semibold text-slate-800">
                                        Related Students
                                    </h2>
                                </div>

                                <p className="mt-1 text-xs text-slate-400">
                                    Only students linked to your parent/guardian account are shown.
                                </p>
                            </div>

                            <div className="divide-y divide-slate-100">
                                {relatedStudents.length > 0 ? (
                                    relatedStudents.map((student) => (
                                        <Link
                                            key={student.id}
                                            href={route('web.students.show', student.id)}
                                            className="block p-5 transition hover:bg-slate-50"
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold text-slate-800">
                                                        {student.name}
                                                    </p>

                                                    <p className="mt-1 text-xs font-medium text-blue-600">
                                                        {student.student_number}
                                                    </p>

                                                    <p className="mt-2 text-xs text-slate-500">
                                                        {student.grade_level
                                                            ? `Grade ${student.grade_level}`
                                                            : 'Grade not available'}
                                                        {student.section ? ` · ${student.section}` : ''}
                                                    </p>
                                                </div>

                                                <div className="shrink-0 text-right">
                                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600">
                                                        {student.status}
                                                    </span>

                                                    <p className="mt-2 text-xs text-slate-400">
                                                        {student.case_count}{' '}
                                                        {student.case_count === 1 ? 'case' : 'cases'}
                                                    </p>
                                                </div>
                                            </div>
                                        </Link>
                                    ))
                                ) : (
                                    <EmptyState
                                        text="No students are currently linked to your account."
                                        icon={GraduationCap}
                                    />
                                )}
                            </div>
                        </div>

                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-5">
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-2">
                                    <ShieldCheck className="h-4 w-4 text-slate-500" />

                                    <h2 className="text-sm font-semibold text-slate-800">
                                        Case Status Overview
                                    </h2>
                                </div>

                                <p className="mt-1 text-xs text-slate-400">
                                    Current status of cases associated with your related students.
                                </p>
                            </div>

                            <div className="space-y-4 p-5">
                                {statusDistribution.length > 0 ? (
                                    statusDistribution.map((status) => (
                                        <div key={status.status_name}>
                                            <div className="mb-2 flex items-center justify-between gap-3">
                                                <div className="flex min-w-0 items-center gap-2">
                                                    <span
                                                        className={`h-2.5 w-2.5 shrink-0 rounded-full ${
                                                            statusClasses[status.status_name] ?? 'bg-slate-300'
                                                        }`}
                                                    />

                                                    <span className="truncate text-sm font-medium text-slate-700">
                                                        {status.status_name}
                                                    </span>
                                                </div>

                                                <span className="text-xs font-semibold text-slate-600">
                                                    {status.total} · {status.percentage}%
                                                </span>
                                            </div>

                                            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                <div
                                                    className={`h-full rounded-full ${
                                                        statusClasses[status.status_name] ?? 'bg-slate-400'
                                                    }`}
                                                    style={{
                                                        width: `${Math.min(status.percentage, 100)}%`,
                                                    }}
                                                />
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <EmptyState
                                        text="No case status information is available yet."
                                        icon={ShieldCheck}
                                    />
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="grid grid-cols-1 gap-6 lg:grid-cols-12">

                        <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:col-span-8">
                            <div className="border-b border-slate-200 px-5 py-4">
                                <div className="flex items-center gap-2">
                                    <FileText className="h-4 w-4 text-slate-500" />

                                    <h2 className="text-sm font-semibold text-slate-800">
                                        Recent Case Activity
                                    </h2>
                                </div>

                                <p className="mt-1 text-xs text-slate-400">
                                    Recent cases involving your related students.
                                </p>
                            </div>

                            <div className="divide-y divide-slate-100">
                                {recentCases.length > 0 ? (
                                    recentCases.map((report) => (
                                        <div key={report.id} className="p-5">
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                                <div className="min-w-0">
                                                    <p className="text-sm font-semibold text-slate-800">
                                                        {report.incident_title}
                                                    </p>

                                                    <p className="mt-1 text-xs font-medium text-blue-600">
                                                        {report.report_code}
                                                    </p>

                                                    <p className="mt-1 text-xs text-slate-500">
                                                        {report.student_name} · {report.category_name}
                                                    </p>

                                                    <p className="mt-1 text-[11px] text-slate-400">
                                                        Incident date: {formatDate(report.incident_date)}
                                                    </p>
                                                </div>

                                                <span
                                                    className={`inline-flex w-fit items-center rounded-full px-2.5 py-1 text-[11px] font-semibold text-white ${
                                                        statusClasses[report.status_name] ?? 'bg-slate-400'
                                                    }`}
                                                >
                                                    {report.status_name}
                                                </span>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <EmptyState
                                        text="No incident cases have been recorded for your related students this academic year."
                                        icon={FileText}
                                    />
                                )}
                            </div>
                        </div>

                        <div className="rounded-2xl border border-blue-100 bg-blue-50/70 p-5 lg:col-span-4">
                            <div className="flex items-start gap-3">
                                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm">
                                    <ShieldCheck className="h-5 w-5" />
                                </div>

                                <div>
                                    <p className="text-xs font-semibold uppercase tracking-wide text-blue-600">
                                        Case Resolution
                                    </p>

                                    <p className="mt-1 text-2xl font-bold text-slate-900">
                                        {dashboardMetrics.resolution_rate}%
                                    </p>

                                    <p className="mt-2 text-xs leading-5 text-slate-500">
                                        Percentage of academic-year cases associated with your related students that are currently resolved.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function EmptyState({ text, icon: Icon = UserRound }: { text: string; icon?: typeof UserRound }) {
    return (
        <div className="flex flex-col items-center justify-center px-5 py-10 text-center">
            <div className="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                <Icon className="h-5 w-5" />
            </div>

            <p className="text-sm text-slate-500">{text}</p>
        </div>
    );
}
