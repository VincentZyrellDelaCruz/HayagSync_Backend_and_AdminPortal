import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { Category, Report, Paginated, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, ArrowRight, Broom, CalendarClock, CheckCircle2, ChevronDown, Clock3, FileWarning, Filter, FolderOpen, Search, ShieldAlert, UserRound, Users, } from 'lucide-react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface ReportUser {
    first_name: string;
    last_name: string;
}

interface ReportsIndexProps {
    reports: Paginated<Report>;
    categories: Category[];
    statuses: string[];
    selectedStatus: string;
    selectedCategory: string | number;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Incident Reports',
        href: '/reports',
    },
];

const statusDotColorClass = (status: string, isActive: boolean) => {
    const map: Record<string, { active: string; inactive: string }> = {
        Pending: { active: 'text-amber-200', inactive: 'text-amber-500' },
        'Under Investigation': { active: 'text-orange-200', inactive: 'text-orange-500' },
        Scheduled: { active: 'text-blue-200', inactive: 'text-blue-500' },
        Escalated: { active: 'text-purple-200', inactive: 'text-purple-500' },
        Resolved: { active: 'text-emerald-200', inactive: 'text-emerald-500' },
        Dismissed: { active: 'text-rose-200', inactive: 'text-rose-500' },
    };

    return map[status]
        ? (isActive ? map[status].active : map[status].inactive)
        : '';
};

const badgeClasses = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
        case 'Under Investigation':
            return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
        case 'Scheduled':
            return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
        case 'Escalated':
            return 'bg-purple-100 text-purple-700 ring-1 ring-purple-200';
        case 'Resolved':
            return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
        case 'Dismissed':
            return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
        default:
            return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
    }
};

const dotClasses = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-amber-500';
        case 'Under Investigation':
            return 'bg-orange-500';
        case 'Scheduled':
            return 'bg-blue-500';
        case 'Escalated':
            return 'bg-purple-500';
        case 'Resolved':
            return 'bg-emerald-500';
        case 'Dismissed':
            return 'bg-rose-500';
        default:
            return 'bg-slate-300';
    }
};

const rowClasses = (status: string) => {
    let classes = 'group block border-l-4 border-transparent px-4 py-4 transition duration-150 hover:bg-slate-50 sm:px-5';

    if (status === 'Pending') {
        classes += ' bg-amber-50/60 border-amber-500';
    } else if (status === 'Under Investigation') {
        classes += ' bg-orange-50/60 border-orange-500';
    } else if (status === 'Escalated') {
        classes += ' bg-purple-50/50 border-purple-500';
    }

    return classes;
};

const titleClasses = (status: string) => {
    if (status === 'Pending') {
        return 'text-amber-950';
    }

    if (status === 'Under Investigation') {
        return 'text-orange-950';
    }

    if (status === 'Escalated') {
        return 'text-purple-950';
    }

    return 'text-slate-900';
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
    });

const formatTime = (value: string) =>
    new Date(value).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });

const formatIncidentDateTime = (
    incidentDate?: string | null,
    incidentTime?: string | null,
) => {
    if (!incidentDate) {
        return null;
    }

    const date = new Date(
        `${incidentDate}T${incidentTime || '00:00:00'}`,
    );

    if (Number.isNaN(date.getTime())) {
        return incidentDate;
    }

    return incidentTime
        ? `${formatDate(date.toISOString())} • ${formatTime(date.toISOString())}`
        : formatDate(date.toISOString());
};

const severityClasses = (severity?: string | null) => {
    switch ((severity ?? '').toLowerCase()) {
        case 'critical':
            return 'bg-red-100 text-red-700 ring-1 ring-red-200';
        case 'high':
            return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
        case 'medium':
            return 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200';
        case 'low':
            return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
        default:
            return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200';
    }
};

export default function ReportsIndex({
    reports,
    categories,
    statuses,
    selectedStatus,
    selectedCategory,
}: ReportsIndexProps) {
    const buildStatusQuery = (status: string) => ({
        status: status === 'all' ? undefined : status,
        category:
            selectedCategory !== 'all'
                ? selectedCategory
                : undefined,
    });

    const buildCategoryQuery = (categoryId?: number) => ({
        status:
            selectedStatus !== 'all'
                ? selectedStatus
                : undefined,
        category: categoryId,
    });

    const selectedCategoryName =
        selectedCategory === 'all'
            ? 'All Categories'
            : categories.find(
                (category) =>
                    String(category.id) ===
                    String(selectedCategory),
            )?.category_name ?? 'All Categories';

    const activeStatuses = statuses.filter(
        (status) => status !== 'all',
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incident Reports" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                            <div className="min-w-0">
                                <div className="mb-2 inline-flex items-center gap-2 rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                    <FolderOpen className="h-3.5 w-3.5" />
                                    Case Management
                                </div>

                                <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                    Incident Reports
                                </h1>

                                <p className="mt-1 max-w-2xl text-sm text-slate-500">
                                    Review, monitor, and organize reported
                                    incidents and their current status.
                                </p>
                            </div>

                            <div className="flex items-center gap-2">
                                <div className="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm">
                                    <Users className="h-4 w-4 text-slate-400" />
                                    {reports.total}{' '}
                                    {reports.total === 1
                                        ? 'incident'
                                        : 'incidents'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Status Overview */}
                    <div className="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                        {activeStatuses.map((status) => {
                            const count =
                                reports.data.filter(
                                    (report) =>
                                        report.current_status
                                            ?.status_name ===
                                        status,
                                ).length;

                            return (
                                <Link
                                    key={status}
                                    href={route(
                                        'web.reports.index',
                                        buildStatusQuery(status),
                                    )}
                                    className={`rounded-2xl border bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md ${
                                        selectedStatus === status
                                            ? 'border-slate-400 ring-2 ring-slate-200'
                                            : 'border-slate-200'
                                    }`}
                                >
                                    <div className="mb-3 flex items-center justify-between gap-2">
                                        <span
                                            className={`h-2.5 w-2.5 rounded-full ${dotClasses(
                                                status,
                                            )}`}
                                        />

                                        {status ===
                                            'Escalated' && (
                                            <ShieldAlert className="h-4 w-4 text-purple-500" />
                                        )}

                                        {status === 'Resolved' && (
                                            <CheckCircle2 className="h-4 w-4 text-emerald-500" />
                                        )}

                                        {status === 'Pending' && (
                                            <AlertTriangle className="h-4 w-4 text-amber-500" />
                                        )}
                                    </div>

                                    <p className="text-xl font-bold text-slate-900">
                                        {count}
                                    </p>

                                    <p className="mt-0.5 text-xs text-slate-500">
                                        {status}
                                    </p>
                                </Link>
                            );
                        })}
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        {/* Sidebar Filters */}
                        <aside className="lg:col-span-3">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm lg:sticky lg:top-6">
                                <div className="border-b border-slate-200 bg-slate-50 px-5 py-4">
                                    <div className="flex items-center gap-2">
                                        <Filter className="h-4 w-4 text-slate-500" />

                                        <h2 className="text-sm font-semibold text-slate-700">
                                            Filters
                                        </h2>
                                    </div>

                                    <p className="mt-1 text-xs text-slate-500">
                                        Narrow down the incident reports.
                                    </p>
                                </div>

                                <div className="space-y-5 p-4 sm:p-5">
                                    <div>
                                        <Link
                                            href={route(
                                                'web.reports.index',
                                            )}
                                            className="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <span>
                                                Clear Filters
                                            </span>

                                            <Broom className="h-4 w-4 text-slate-400" />
                                        </Link>
                                    </div>

                                    {/* Status Filter */}
                                    <div>
                                        <h3 className="mb-3 text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                            Current Status
                                        </h3>

                                        <div className="space-y-1">
                                            {statuses.map(
                                                (status) => {
                                                    const isActive =
                                                        selectedStatus ===
                                                        status;

                                                    const label =
                                                        status ===
                                                            'all'
                                                            ? 'All Statuses'
                                                            : status;

                                                    return (
                                                        <Link
                                                            key={status}
                                                            href={route(
                                                                'web.reports.index',
                                                                buildStatusQuery(
                                                                    status,
                                                                ),
                                                            )}
                                                            className={`flex items-center justify-between rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                                                isActive
                                                                    ? 'bg-slate-900 text-white shadow-sm'
                                                                    : 'text-slate-700 hover:bg-slate-100'
                                                            }`}
                                                        >
                                                            <span>
                                                                {label}
                                                            </span>

                                                            {status !==
                                                                'all' && (
                                                                <span
                                                                    className={
                                                                        statusDotColorClass(
                                                                            status,
                                                                            isActive,
                                                                        ) +
                                                                        ' text-xs'
                                                                    }
                                                                >
                                                                    ●
                                                                </span>
                                                            )}
                                                        </Link>
                                                    );
                                                },
                                            )}
                                        </div>
                                    </div>

                                    {/* Category Filter */}
                                    <div>
                                        <div className="mb-3 flex items-center justify-between gap-2">
                                            <h3 className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">
                                                Incident Category
                                            </h3>

                                            <ChevronDown className="h-3.5 w-3.5 text-slate-400" />
                                        </div>

                                        <div className="max-h-72 space-y-1 overflow-y-auto pr-1">
                                            <Link
                                                href={route(
                                                    'web.reports.index',
                                                    buildCategoryQuery(),
                                                )}
                                                className={`block rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                                    selectedCategory ===
                                                        'all'
                                                        ? 'bg-slate-900 text-white shadow-sm'
                                                        : 'text-slate-700 hover:bg-slate-100'
                                                }`}
                                            >
                                                All Categories
                                            </Link>

                                            {categories.map(
                                                (category) => {
                                                    const isCategoryActive =
                                                        String(
                                                            selectedCategory,
                                                        ) ===
                                                        String(
                                                            category.id,
                                                        );

                                                    return (
                                                        <Link
                                                            key={
                                                                category.id
                                                            }
                                                            href={route(
                                                                'web.reports.index',
                                                                buildCategoryQuery(
                                                                    category.id,
                                                                ),
                                                            )}
                                                            className={`block rounded-xl px-3 py-2.5 text-sm font-medium transition ${
                                                                isCategoryActive
                                                                    ? 'bg-slate-900 text-white shadow-sm'
                                                                    : 'text-slate-700 hover:bg-slate-100'
                                                            }`}
                                                        >
                                                            {
                                                                category.category_name
                                                            }
                                                        </Link>
                                                    );
                                                },
                                            )}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        {/* Incident List */}
                        <section className="lg:col-span-9">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                {/* Top Bar */}
                                <div className="border-b border-slate-200 bg-white px-4 py-4 sm:px-5">
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <Search className="h-4 w-4 text-slate-400" />

                                                <h2 className="text-sm font-semibold text-slate-800">
                                                    Filtered Incidents
                                                </h2>
                                            </div>

                                            <p className="mt-1 text-xs text-slate-500">
                                                {reports.total}{' '}
                                                {reports.total ===
                                                    1
                                                    ? 'incident'
                                                    : 'incidents'}{' '}
                                                found
                                            </p>
                                        </div>

                                        <div className="flex flex-wrap gap-2">
                                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                Status:{' '}
                                                {selectedStatus ===
                                                    'all'
                                                    ? 'All'
                                                    : selectedStatus}
                                            </span>

                                            <span className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600">
                                                {selectedCategoryName}
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* List */}
                                <div className="divide-y divide-slate-200">
                                    {reports.data.length > 0 ? (
                                        reports.data.map(
                                            (report) => {
                                                const status =
                                                    report
                                                        .current_status
                                                        ?.status_name ??
                                                    'No Status';

                                                const needsAttention =
                                                    [
                                                        'Pending',
                                                        'Under Investigation',
                                                        'Escalated',
                                                    ].includes(
                                                        status,
                                                    );

                                                const reporter =
                                                    report.user
                                                        ? `${report.user.last_name}, ${report.user.first_name}`
                                                        : 'Unknown Reporter';

                                                const assignee =
                                                    report
                                                        .current_assignee
                                                        ?.user
                                                        ? `${report.current_assignee.user.last_name}, ${report.current_assignee.user.first_name}`
                                                        : null;

                                                const currentLevel =
                                                    report.current_level ??
                                                    report.escalation_level ??
                                                    null;

                                                const incidentDateTime =
                                                    formatIncidentDateTime(
                                                        report.incident_date,
                                                        report.incident_time,
                                                    );

                                                return (
                                                    <Link
                                                        key={
                                                            report.id
                                                        }
                                                        href={route(
                                                            'web.reports.show',
                                                            report.id,
                                                        )}
                                                        className={rowClasses(
                                                            status,
                                                        )}
                                                    >
                                                        <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                                            <div className="flex min-w-0 flex-1 items-start gap-3.5">
                                                                <div className="mt-1 shrink-0">
                                                                    <span
                                                                        className={`block h-3 w-3 rounded-full ring-4 ring-white ${dotClasses(
                                                                            status,
                                                                        )}`}
                                                                    />
                                                                </div>

                                                                <div className="min-w-0 flex-1">
                                                                    <div className="flex flex-wrap items-center gap-2">
                                                                        <h3
                                                                            className={`min-w-0 text-sm font-semibold sm:text-base ${titleClasses(
                                                                                status,
                                                                            )}`}
                                                                        >
                                                                            {
                                                                                report.incident_title
                                                                            }
                                                                        </h3>

                                                                        <span
                                                                            className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold ${badgeClasses(
                                                                                status,
                                                                            )}`}
                                                                        >
                                                                            {
                                                                                status
                                                                            }
                                                                        </span>

                                                                        {report.read_only && (
                                                                            <span className="shrink-0 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-500 ring-1 ring-slate-200">
                                                                                Read only
                                                                            </span>
                                                                        )}

                                                                        {report.severity && (
                                                                            <span
                                                                                className={`shrink-0 rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ${severityClasses(
                                                                                    report.severity,
                                                                                )}`}
                                                                            >
                                                                                {
                                                                                    report.severity
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </div>

                                                                    <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-slate-500 sm:text-sm">
                                                                        <span>
                                                                            <span className="font-medium text-slate-700">
                                                                                Reported by:
                                                                            </span>{' '}
                                                                            {
                                                                                reporter
                                                                            }
                                                                        </span>

                                                                        {report.category && (
                                                                            <span>
                                                                                <span className="font-medium text-slate-700">
                                                                                    Category:
                                                                                </span>{' '}
                                                                                {
                                                                                    report
                                                                                        .category
                                                                                        .category_name
                                                                                }
                                                                            </span>
                                                                        )}

                                                                        {report.location && (
                                                                            <span className="max-w-full truncate">
                                                                                <span className="font-medium text-slate-700">
                                                                                    Location:
                                                                                </span>{' '}
                                                                                {
                                                                                    report.location
                                                                                }
                                                                            </span>
                                                                        )}
                                                                    </div>

                                                                    {report.description && (
                                                                        <p className="mt-2 line-clamp-2 text-xs leading-5 text-slate-500 sm:text-sm">
                                                                            {
                                                                                report.description
                                                                            }
                                                                        </p>
                                                                    )}

                                                                    {(currentLevel ||
                                                                        assignee) && (
                                                                        <div className="mt-3 flex flex-wrap gap-2">
                                                                            {assignee && (
                                                                                <span className="rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-medium text-slate-600">
                                                                                    Last Assigned:{' '}
                                                                                    {
                                                                                        assignee
                                                                                    }
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </div>

                                                            <div className="flex shrink-0 items-center justify-between gap-3 text-xs text-slate-500 xl:flex-col xl:items-end">
                                                                <div className="text-left xl:text-right">
                                                                    {report.report_code && (
                                                                        <p className="mb-1 font-mono text-[11px] font-medium text-slate-400">
                                                                            {
                                                                                report.report_code
                                                                            }
                                                                        </p>
                                                                    )}

                                                                    <p className="font-medium text-slate-700">
                                                                        {
                                                                            formatDate(
                                                                                report.created_at,
                                                                            )
                                                                        }
                                                                    </p>

                                                                    <p className="mt-0.5 text-slate-400">
                                                                        {
                                                                            formatTime(
                                                                                report.created_at,
                                                                            )
                                                                        }
                                                                    </p>
                                                                </div>

                                                                {needsAttention && (
                                                                    <span className="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-semibold text-red-700 ring-1 ring-red-100">
                                                                        <AlertTriangle className="h-3 w-3" />
                                                                        Needs Attention
                                                                    </span>
                                                                )}

                                                                <ArrowRight className="hidden h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500 xl:block" />
                                                            </div>
                                                        </div>
                                                    </Link>
                                                );
                                            },
                                        )
                                    ) : (
                                        <div className="px-6 py-16 text-center">
                                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                                <FileWarning className="h-6 w-6 text-slate-400" />
                                            </div>

                                            <h3 className="text-base font-semibold text-slate-700">
                                                No reports found
                                            </h3>

                                            <p className="mt-1 text-sm text-slate-500">
                                                No cases match the selected
                                                filters.
                                            </p>
                                        </div>
                                    )}
                                </div>

                                {/* Pagination */}
                                {reports.links.length > 3 && (
                                    <div className="border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                                        <Pagination
                                            links={reports.links}
                                            meta={{
                                                from: reports.from,
                                                to: reports.to,
                                                total: reports.total,
                                            }}
                                        />
                                    </div>
                                )}
                            </div>
                        </section>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
