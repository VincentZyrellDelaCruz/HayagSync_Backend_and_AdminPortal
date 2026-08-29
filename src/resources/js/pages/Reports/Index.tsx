import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

// NOTE: this project's starter kit ships Ziggy, which exposes a global
// `route()` helper (see resources/js/ziggy.js / vendor/tightenco/ziggy).
// If your setup doesn't expose it globally, `import { route } from 'ziggy-js'`.
declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface ReportUser {
    first_name: string;
    last_name: string;
}

interface Category {
    id: number;
    category_name: string;
}

interface ReportStatus {
    status_name: string;
}

interface Report {
    id: number;
    incident_title: string;
    description: string | null;
    location: string | null;
    created_at: string;
    user: ReportUser | null;
    category: Category | null;
    current_status: ReportStatus | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Paginated<T> {
    data: T[];
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
}

interface ReportsIndexProps {
    reports: Paginated<Report>;
    categories: Category[];
    statuses: string[];
    selectedStatus: string;
    selectedCategory: string | number;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Incident Inbox', href: '/reports' }];

const statusDotColorClass = (status: string, isActive: boolean) => {
    const map: Record<string, { active: string; inactive: string }> = {
        Pending: { active: 'text-amber-200', inactive: 'text-amber-500' },
        'Under Investigation': { active: 'text-orange-200', inactive: 'text-orange-500' },
        Scheduled: { active: 'text-blue-200', inactive: 'text-blue-500' },
        Resolved: { active: 'text-green-200', inactive: 'text-green-500' },
        Dropped: { active: 'text-red-200', inactive: 'text-red-500' },
    };
    return map[status] ? (isActive ? map[status].active : map[status].inactive) : '';
};

const badgeClasses = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-amber-100 text-amber-700';
        case 'Under Investigation':
            return 'bg-orange-100 text-orange-700';
        case 'Scheduled':
            return 'bg-blue-100 text-blue-700';
        case 'Resolved':
            return 'bg-green-100 text-green-700';
        case 'Dropped':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const dotClasses = (status: string) => {
    switch (status) {
        case 'Dropped':
            return 'bg-red-500';
        case 'Pending':
            return 'bg-amber-500';
        case 'Under Investigation':
            return 'bg-orange-400';
        case 'Resolved':
            return 'bg-green-500';
        case 'Scheduled':
            return 'bg-blue-500';
        default:
            return 'bg-slate-300';
    }
};

const rowClasses = (status: string) => {
    let classes = 'block px-5 py-4 hover:bg-slate-50 transition duration-150';
    if (status === 'Pending') classes += ' bg-amber-50 border-l-4 border-amber-500';
    else if (status === 'Under Investigation') classes += ' bg-orange-50 border-l-4 border-orange-400';
    return classes;
};

const titleClasses = (status: string) => {
    if (status === 'Pending') return 'text-amber-900 font-semibold';
    if (status === 'Under Investigation') return 'text-orange-900 font-semibold';
    return 'text-slate-800';
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

const formatTime = (value: string) =>
    new Date(value).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

export default function ReportsIndex({ reports, categories, statuses, selectedStatus, selectedCategory }: ReportsIndexProps) {
    const buildStatusQuery = (status: string) => ({
        status: status === 'all' ? undefined : status,
        category: selectedCategory !== 'all' ? selectedCategory : undefined,
    });

    const buildCategoryQuery = (categoryId?: number) => ({
        status: selectedStatus !== 'all' ? selectedStatus : undefined,
        category: categoryId,
    });

    const selectedCategoryName =
        selectedCategory === 'all'
            ? 'All'
            : categories.find((c) => String(c.id) === String(selectedCategory))?.category_name ?? 'All';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Report Management" />

            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                {/* Header */}
                <div className="flex items-center justify-between flex-wrap gap-3 mb-6">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-800">Incident Inbox</h1>
                        <p className="text-sm text-slate-500 mt-1">Review, prioritize, and organize reported incidents.</p>
                    </div>

                    <div className="flex items-center gap-2 text-sm">
                        <span className="px-3 py-1 rounded-full bg-amber-100 text-amber-700 font-medium">Pending First</span>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Sidebar Filters */}
                    <aside className="lg:col-span-3">
                        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6">
                            <div className="px-5 py-4 border-b border-slate-200 bg-slate-50">
                                <h2 className="text-sm font-semibold text-slate-700">Filters</h2>
                                <p className="text-xs text-slate-500 mt-1">Organize cases faster</p>
                            </div>

                            <div className="p-5 space-y-6">
                                {/* Reset */}
                                <div>
                                    <Link
                                        href={route('web.reports.index')}
                                        className="w-full inline-flex items-center justify-center px-4 py-2 rounded-xl border border-slate-200 text-sm font-medium text-slate-700 hover:bg-slate-50 transition"
                                    >
                                        Reset Filters
                                    </Link>
                                </div>

                                {/* Status Filter */}
                                <div>
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Current Status</h3>

                                    <div className="space-y-1">
                                        {statuses.map((status) => {
                                            const isActive = selectedStatus === status;
                                            const label = status === 'all' ? 'All Statuses' : status;

                                            return (
                                                <Link
                                                    key={status}
                                                    href={route('web.reports.index', buildStatusQuery(status))}
                                                    className={`flex items-center justify-between px-3 py-2 rounded-xl text-sm font-medium transition ${
                                                        isActive ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'
                                                    }`}
                                                >
                                                    <span>{label}</span>
                                                    {status !== 'all' && (
                                                        <span className={`text-xs ${statusDotColorClass(status, isActive)}`}>●</span>
                                                    )}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>

                                {/* Category Filter */}
                                <div>
                                    <h3 className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">Incident Category</h3>

                                    <div className="space-y-1">
                                        <Link
                                            href={route('web.reports.index', buildCategoryQuery())}
                                            className={`block px-3 py-2 rounded-xl text-sm font-medium transition ${
                                                selectedCategory === 'all' ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'
                                            }`}
                                        >
                                            All Categories
                                        </Link>

                                        {categories.map((category) => {
                                            const isCategoryActive = String(selectedCategory) === String(category.id);

                                            return (
                                                <Link
                                                    key={category.id}
                                                    href={route('web.reports.index', buildCategoryQuery(category.id))}
                                                    className={`block px-3 py-2 rounded-xl text-sm font-medium transition ${
                                                        isCategoryActive ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'
                                                    }`}
                                                >
                                                    {category.category_name}
                                                </Link>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>

                    {/* Incident List */}
                    <section className="lg:col-span-9">
                        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                            {/* Top Bar */}
                            <div className="px-5 py-4 border-b border-slate-200 bg-slate-50">
                                <div className="flex items-center justify-between flex-wrap gap-3">
                                    <div>
                                        <h2 className="text-sm font-semibold text-slate-700">Filtered Incidents</h2>
                                        <p className="text-xs text-slate-500 mt-1">
                                            {reports.total} total incident{reports.total !== 1 ? 's' : ''}
                                        </p>
                                    </div>

                                    <div className="flex flex-wrap gap-2">
                                        <span className="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                            Status: {selectedStatus === 'all' ? 'All' : selectedStatus}
                                        </span>
                                        <span className="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-medium">
                                            Category: {selectedCategoryName}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* List */}
                            <div className="divide-y divide-slate-200">
                                {reports.data.length > 0 ? (
                                    reports.data.map((report) => {
                                        const status = report.current_status?.status_name ?? 'No Status';
                                        const isPending = ['Pending', 'Under Investigation'].includes(status);
                                        const reporter = report.user
                                            ? `${report.user.last_name}, ${report.user.first_name}`
                                            : 'Unknown Reporter';

                                        return (
                                            <Link key={report.id} href={route('web.reports.show', report.id)} className={rowClasses(status)}>
                                                <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                                                    {/* Left Section */}
                                                    <div className="flex items-start gap-4 min-w-0 flex-1">
                                                        <div className="mt-1 shrink-0">
                                                            <span className={`w-3 h-3 rounded-full block ${dotClasses(status)}`} />
                                                        </div>

                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex flex-wrap items-center gap-2 mb-1">
                                                                <h3 className={`text-sm sm:text-base truncate ${titleClasses(status)}`}>
                                                                    {report.incident_title}
                                                                </h3>
                                                                <span className={`px-2.5 py-1 rounded-full text-xs font-medium ${badgeClasses(status)}`}>
                                                                    {status}
                                                                </span>
                                                            </div>

                                                            <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500">
                                                                <span>
                                                                    <span className="font-medium text-slate-600">Reported by:</span> {reporter}
                                                                </span>

                                                                {report.category && (
                                                                    <span>
                                                                        <span className="font-medium text-slate-600">Category:</span>{' '}
                                                                        {report.category.category_name}
                                                                    </span>
                                                                )}

                                                                {report.location && (
                                                                    <span className="truncate">
                                                                        <span className="font-medium text-slate-600">Location:</span>{' '}
                                                                        {report.location}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            {report.description && (
                                                                <p className="mt-2 text-sm text-slate-500 line-clamp-1">{report.description}</p>
                                                            )}
                                                        </div>
                                                    </div>

                                                    {/* Right Section */}
                                                    <div className="flex lg:flex-col items-start lg:items-end justify-between gap-2 shrink-0 text-sm">
                                                        <div className="text-slate-700 font-medium">{formatDate(report.created_at)}</div>
                                                        <div className="text-xs text-slate-500">{formatTime(report.created_at)}</div>

                                                        {isPending && (
                                                            <span className="mt-1 inline-flex items-center px-2.5 py-1 rounded-full bg-red-100 text-red-700 text-xs font-semibold">
                                                                Needs Attention
                                                            </span>
                                                        )}
                                                    </div>
                                                </div>
                                            </Link>
                                        );
                                    })
                                ) : (
                                    <div className="px-6 py-16 text-center">
                                        <div className="mx-auto w-14 h-14 rounded-full bg-slate-100 flex items-center justify-center mb-4">
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                className="w-6 h-6 text-slate-400"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth={1.8}
                                                    d="M9 12h6m-6 4h6M7 4h10a2 2 0 012 2v12a2 2 0 01-2 2H7a2 2 0 01-2-2V6a2 2 0 012-2z"
                                                />
                                            </svg>
                                        </div>
                                        <h3 className="text-lg font-semibold text-slate-700">No reports found</h3>
                                        <p className="text-sm text-slate-500 mt-2">No cases match the selected filters.</p>
                                    </div>
                                )}
                            </div>

                            {/* Pagination */}
                            {reports.links.length > 3 && (
                                <div className="px-5 py-4 border-t border-slate-200 bg-white">
                                    <Pagination
                                        links={reports.links}
                                        meta={{ from: reports.from, to: reports.to, total: reports.total }}
                                    />
                                </div>
                            )}
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
