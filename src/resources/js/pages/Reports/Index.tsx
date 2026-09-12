import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { Category, Report, Paginated, type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle, ArrowRight, Broom, CalendarCheck2, CheckCircle2, CircleDot, Clock3,
    FileWarning, Filter, Inbox, Layers3, Search, ShieldAlert, SlidersHorizontal, Users, X, XCircle,
    type LucideIcon,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface ReportUser { first_name: string; last_name: string; }

interface ReportListItem extends Report {
    urgent_safety_flag?: boolean | null;
    can_act?: boolean;
    read_only?: boolean;
    current_level?: number | null;
    escalation_level?: number | null;
    current_assignee?: { user?: ReportUser | null } | null;
    severity?: string | null;
}

interface ReportsIndexProps {
    reports: Paginated<ReportListItem>;
    categories: Category[];
    statuses: string[];
    selectedStatus: string;
    selectedCategory: string | number;
    search: string;
    statusCounts: Record<string, number>;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Incident Reports', href: '/reports' }];

const STATUS_META: Record<string, { label: string; icon: LucideIcon; dot: string; active: string; soft: string }> = {
    all: { label: 'All Reports', icon: Inbox, dot: 'bg-slate-500', active: 'border-slate-400 bg-slate-900 text-white dark:border-slate-600 dark:bg-white dark:text-slate-900', soft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200' },
    Pending: { label: 'Pending', icon: Clock3, dot: 'bg-amber-500', active: 'border-amber-400 bg-amber-600 text-white', soft: 'bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300' },
    'Under Investigation': { label: 'Under Investigation', icon: Search, dot: 'bg-orange-500', active: 'border-orange-400 bg-orange-600 text-white', soft: 'bg-orange-50 text-orange-700 dark:bg-orange-950/40 dark:text-orange-300' },
    Scheduled: { label: 'Scheduled', icon: CalendarCheck2, dot: 'bg-blue-500', active: 'border-blue-400 bg-blue-600 text-white', soft: 'bg-blue-50 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300' },
    Escalated: { label: 'Escalated', icon: ShieldAlert, dot: 'bg-purple-500', active: 'border-purple-400 bg-purple-600 text-white', soft: 'bg-purple-50 text-purple-700 dark:bg-purple-950/40 dark:text-purple-300' },
    Resolved: { label: 'Resolved', icon: CheckCircle2, dot: 'bg-emerald-500', active: 'border-emerald-400 bg-emerald-600 text-white', soft: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300' },
    Dismissed: { label: 'Dismissed', icon: XCircle, dot: 'bg-rose-500', active: 'border-rose-400 bg-rose-600 text-white', soft: 'bg-rose-50 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300' },
};

const getStatusMeta = (status: string) => STATUS_META[status] ?? {
    label: status, icon: CircleDot, dot: 'bg-slate-400', active: 'border-slate-400 bg-slate-700 text-white', soft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
};

const badgeClasses = (status: string) => {
    switch (status) {
        case 'Pending': return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:ring-amber-900';
        case 'Under Investigation': return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:ring-orange-900';
        case 'Scheduled': return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-900';
        case 'Escalated': return 'bg-purple-100 text-purple-700 ring-1 ring-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:ring-purple-900';
        case 'Resolved': return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:ring-emerald-900';
        case 'Dismissed': return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-950/50 dark:text-rose-300 dark:ring-rose-900';
        default: return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700';
    }
};

const rowClasses = (status: string, urgent: boolean) => {
    let classes = 'group block border-l-4 px-3 py-4 transition duration-150 hover:bg-slate-50 dark:hover:bg-slate-900/70 sm:px-5';
    if (urgent) classes += ' border-red-500 bg-red-50/70 dark:border-red-500 dark:bg-red-950/20';
    else if (status === 'Pending') classes += ' border-amber-500 bg-amber-50/50 dark:border-amber-500 dark:bg-amber-950/10';
    else if (status === 'Under Investigation') classes += ' border-orange-500 bg-orange-50/50 dark:border-orange-500 dark:bg-orange-950/10';
    else if (status === 'Escalated') classes += ' border-purple-500 bg-purple-50/40 dark:border-purple-500 dark:bg-purple-950/10';
    else classes += ' border-transparent';
    return classes;
};

const titleClasses = (status: string, urgent: boolean) => {
    if (urgent) return 'text-red-950 dark:text-red-200';
    if (status === 'Pending') return 'text-amber-950 dark:text-amber-200';
    if (status === 'Under Investigation') return 'text-orange-950 dark:text-orange-200';
    if (status === 'Escalated') return 'text-purple-950 dark:text-purple-200';
    return 'text-slate-900 dark:text-white';
};

const formatDate = (value: string) => new Date(value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
const formatTime = (value: string) => new Date(value).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

const severityClasses = (severity?: string | null) => {
    switch ((severity ?? '').toLowerCase()) {
        case 'critical': return 'bg-red-100 text-red-700 ring-1 ring-red-200 dark:bg-red-950/50 dark:text-red-300 dark:ring-red-900';
        case 'high': return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200 dark:bg-orange-950/50 dark:text-orange-300 dark:ring-orange-900';
        case 'medium': return 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200 dark:bg-yellow-950/50 dark:text-yellow-300 dark:ring-yellow-900';
        case 'low': return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:ring-blue-900';
        default: return 'bg-slate-100 text-slate-600 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-700';
    }
};

export default function ReportsIndex({ reports, categories, statuses, selectedStatus, selectedCategory, search, statusCounts }: ReportsIndexProps) {
    const [searchValue, setSearchValue] = useState(search ?? '');
    const [isSearching, setIsSearching] = useState(false);
    const skipSearchEffect = useRef(false);

    useEffect(() => setSearchValue(search ?? ''), [search]);

    useEffect(() => {
        const nextSearch = searchValue.trim();
        if (skipSearchEffect.current) {
            skipSearchEffect.current = false;
            return;
        }

        if (nextSearch === search.trim()) return;

        const timer = window.setTimeout(() => {
            setIsSearching(true);
            router.get(route('web.reports.index'), {
                status: selectedStatus !== 'all' ? selectedStatus : undefined,
                category: selectedCategory !== 'all' ? selectedCategory : undefined,
                search: nextSearch || undefined,
            }, {
                only: ['reports', 'selectedStatus', 'selectedCategory', 'search', 'statusCounts'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsSearching(false),
            });
        }, 650);

        return () => window.clearTimeout(timer);
    }, [searchValue, search, selectedStatus, selectedCategory]);

    const statusList = ['all', ...statuses.filter((status) => status !== 'all')].filter((status, index, list) => list.indexOf(status) === index);

    const selectedCategoryName = selectedCategory === 'all'
        ? 'All Categories'
        : categories.find((category) => String(category.id) === String(selectedCategory))?.category_name ?? 'All Categories';

    const navigateFilters = (nextStatus: string = selectedStatus, nextCategory: string | number = selectedCategory, nextSearch: string = searchValue) => {
        router.get(route('web.reports.index'), {
            status: nextStatus !== 'all' ? nextStatus : undefined,
            category: nextCategory !== 'all' ? nextCategory : undefined,
            search: nextSearch.trim() || undefined,
        }, {
            only: ['reports', 'selectedStatus', 'selectedCategory', 'search', 'statusCounts'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const clearFilters = () => {
        skipSearchEffect.current = true;
        setSearchValue('');
        navigateFilters('all', 'all', '');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incident Reports" />

            <div className="min-h-full bg-slate-50/70 dark:bg-slate-950">
                <div className="mx-auto w-full max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    {/* Header */}
                    <div className="mb-5 flex flex-col gap-4 sm:mb-6 lg:flex-row lg:items-end lg:justify-between">
                        <div className="flex min-w-0 items-start gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900">
                                <Layers3 className="h-5 w-5" />
                            </div>
                            <div className="min-w-0">
                                <h1 className="wrap-break-word text-2xl font-bold tracking-tight text-slate-900 dark:text-white sm:text-3xl">Incident Reports</h1>
                                <p className="mt-0.5 text-sm text-slate-500 dark:text-slate-400">Review, monitor, and organize reported incidents.</p>
                            </div>
                        </div>

                        <div className="flex w-full sm:w-auto sm:justify-end">
                            <div className="inline-flex max-w-full items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-600 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                                <Users className="h-4 w-4 shrink-0 text-slate-400" />
                                <span className="truncate">{reports.total} {reports.total === 1 ? 'incident' : 'incidents'}</span>
                            </div>
                        </div>
                    </div>

                    {/* Search */}
                    <div className="mb-5 sm:mb-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-2 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <div className="flex min-w-0 items-center gap-2">
                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-300 sm:h-10 sm:w-10">
                                    <Search className="h-4 w-4" />
                                </div>
                                <input
                                    type="search"
                                    value={searchValue}
                                    onChange={(event) => setSearchValue(event.target.value)}
                                    placeholder="Search reports..."
                                    className="min-w-0 flex-1 border-0 bg-transparent px-1.5 py-2 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-white dark:placeholder:text-slate-500 sm:px-2"
                                    aria-label="Search incident reports"
                                />
                                {isSearching && <span className="hidden shrink-0 text-xs font-medium text-slate-400 xs:inline sm:inline">Searching…</span>}
                                {searchValue && !isSearching && (
                                    <button type="button" onClick={() => setSearchValue('')} className="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 dark:hover:bg-slate-800 dark:hover:text-white" aria-label="Clear search">
                                        <X className="h-4 w-4" />
                                    </button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Status */}
                    <section className="mb-5 sm:mb-6">
                        <div className="mb-3 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                            <div className="min-w-0">
                                <h2 className="text-sm font-semibold text-slate-800 dark:text-slate-200">Status</h2>
                            </div>
                            <span className="hidden shrink-0 rounded-full bg-slate-100 px-3 py-1 text-[11px] font-medium text-slate-500 dark:bg-slate-800 dark:text-slate-400 sm:inline-flex">Live filter</span>
                        </div>

                        <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 sm:gap-3 lg:grid-cols-7">
                            {statusList.map((status, index) => {
                                const meta = getStatusMeta(status);
                                const Icon = meta.icon;
                                const isActive = selectedStatus === status;
                                const count = statusCounts[status] ?? 0;

                                return (
                                    <Link
                                        key={status}
                                        href={route('web.reports.index', {
                                            status: status !== 'all' ? status : undefined,
                                            category: selectedCategory !== 'all' ? selectedCategory : undefined,
                                            search: searchValue.trim() || undefined,
                                        })}
                                        preserveState
                                        preserveScroll
                                        replace
                                        className={`min-w-0 rounded-xl border p-3 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md sm:rounded-2xl sm:p-4 ${
                                            isActive
                                                ? meta.active
                                                : 'border-slate-200 bg-white text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200'
                                        } ${index === 0 ? 'col-span-2 sm:col-span-1' : ''}`}
                                    >
                                        <div className="mb-3 flex items-center justify-between gap-2 sm:mb-4">
                                            <span className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg sm:h-9 sm:w-9 sm:rounded-xl ${isActive ? 'bg-white/15' : meta.soft}`}>
                                                <Icon className="h-4 w-4" />
                                            </span>
                                            <span className={`h-2 w-2 shrink-0 rounded-full sm:h-2.5 sm:w-2.5 ${meta.dot}`} />
                                        </div>

                                        <p className="text-xl font-bold leading-none sm:text-2xl">{count}</p>
                                        <p className={`mt-1.5 wrap-break-word text-[11px] font-medium leading-4 sm:mt-2 sm:text-xs ${isActive ? 'text-current/80' : 'text-slate-500 dark:text-slate-400'}`}>
                                            {meta.label}
                                        </p>
                                    </Link>
                                );
                            })}
                        </div>
                    </section>

                    <div className="grid grid-cols-1 gap-5 sm:gap-6 lg:grid-cols-12">

                        {/* Category Filter */}
                        <aside className="min-w-0 lg:col-span-3">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-6">
                                <div className="border-b border-slate-200 bg-slate-50/80 px-4 py-4 dark:border-slate-800 dark:bg-slate-950/70 sm:px-5">
                                    <div className="flex items-center gap-2">
                                        <SlidersHorizontal className="h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" />
                                        <h2 className="text-sm font-semibold text-slate-800 dark:text-slate-200">Refine reports</h2>
                                    </div>
                                    <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">Use categories to narrow the current view.</p>
                                </div>

                                <div className="space-y-4 p-4 sm:space-y-5 sm:p-5">
                                    <button
                                        type="button"
                                        onClick={clearFilters}
                                        className="flex w-full items-center justify-between rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <span>Clear filters</span>
                                        <Broom className="h-4 w-4 shrink-0 text-slate-400" />
                                    </button>

                                    <div>
                                        <div className="mb-3 flex items-center justify-between gap-2">
                                            <h3 className="text-[11px] font-semibold uppercase tracking-wider text-slate-400">Incident category</h3>
                                            <Filter className="h-3.5 w-3.5 shrink-0 text-slate-400" />
                                        </div>

                                        <div className="max-h-64 space-y-1 overflow-y-auto pr-1 sm:max-h-80">
                                            <button
                                                type="button"
                                                onClick={() => navigateFilters(selectedStatus, 'all')}
                                                className={`flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition ${
                                                    selectedCategory === 'all'
                                                        ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                                                        : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                                                }`}
                                            >
                                                <span className="min-w-0 truncate">All Categories</span>
                                                {selectedCategory === 'all' && <CheckCircle2 className="h-4 w-4 shrink-0" />}
                                            </button>

                                            {categories.map((category) => {
                                                const isActive = String(selectedCategory) === String(category.id);

                                                return (
                                                    <button
                                                        key={category.id}
                                                        type="button"
                                                        onClick={() => navigateFilters(selectedStatus, category.id)}
                                                        className={`flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition ${
                                                            isActive
                                                                ? 'bg-slate-900 text-white shadow-sm dark:bg-white dark:text-slate-900'
                                                                : 'text-slate-700 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800'
                                                        }`}
                                                    >
                                                        <span className="min-w-0 truncate">{category.category_name}</span>
                                                        {isActive && <CheckCircle2 className="h-4 w-4 shrink-0" />}
                                                    </button>
                                                );
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </aside>

                        {/* Incident List */}
                        <section className="min-w-0 lg:col-span-9">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">

                                {/* Top Bar */}
                                <div className="border-b border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-5">
                                    <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                        <div className="min-w-0">
                                            <div className="flex items-center gap-2">
                                                <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800">
                                                    <Inbox className="h-4 w-4 text-slate-500 dark:text-slate-300" />
                                                </div>
                                                <h2 className="text-sm font-semibold text-slate-800 dark:text-slate-200">Reported incidents</h2>
                                            </div>
                                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                                {reports.total} {reports.total === 1 ? 'incident' : 'incidents'} found
                                            </p>
                                        </div>

                                        <div className="flex max-w-full flex-wrap gap-2">
                                            <span className="max-w-full rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                <span className="wrap-break-word">Status: {getStatusMeta(selectedStatus).label}</span>
                                            </span>
                                            <span className="max-w-full rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                <span className="wrap-break-word">{selectedCategoryName}</span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {/* List */}
                                <div className="divide-y divide-slate-200 dark:divide-slate-800">
                                    {reports.data.length > 0 ? reports.data.map((report) => {
                                        const status = report.current_status?.status_name ?? 'No Status';
                                        const urgent = report.urgent_safety_flag === true;
                                        const reporter = report.user ? `${report.user.last_name}, ${report.user.first_name}` : 'Unknown Reporter';
                                        const assignee = report.current_assignee?.user
                                            ? `${report.current_assignee.user.last_name}, ${report.current_assignee.user.first_name}`
                                            : null;
                                        const currentLevel = report.current_level ?? report.escalation_level ?? null;

                                        // The server is authoritative; a current handler with can_act=true must never display Read only.
                                        const isReadOnly = report.can_act !== true && report.read_only === true;

                                        return (
                                            <Link key={report.id} href={route('web.reports.show', report.id)} className={rowClasses(status, urgent)}>
                                                <div className="flex min-w-0 flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                                                    <div className="flex min-w-0 flex-1 items-start gap-3">
                                                        <div className="mt-1 shrink-0">
                                                            <span className={`block h-2.5 w-2.5 rounded-full ring-4 ring-white dark:ring-slate-900 sm:h-3 sm:w-3 ${urgent ? 'bg-red-500' : getStatusMeta(status).dot}`} />
                                                        </div>

                                                        <div className="min-w-0 flex-1">
                                                            <div className="flex min-w-0 flex-wrap items-center gap-1.5 sm:gap-2">
                                                                <h3 className={`min-w-0 max-w-full wrap-break-word text-sm font-semibold sm:text-base ${titleClasses(status, urgent)}`}>
                                                                    {report.incident_title}
                                                                </h3>

                                                                <span className={`shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold sm:px-2.5 sm:text-[11px] ${badgeClasses(status)}`}>
                                                                    {status}
                                                                </span>

                                                                {urgent && (
                                                                    <span className="inline-flex shrink-0 items-center gap-1 rounded-full bg-red-100 px-2 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-200 dark:bg-red-950/60 dark:text-red-300 dark:ring-red-900 sm:px-2.5 sm:text-[11px]">
                                                                        <AlertTriangle className="h-3 w-3" />
                                                                        Needs Attention
                                                                    </span>
                                                                )}

                                                                {isReadOnly && (
                                                                    <span className="shrink-0 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:ring-slate-700 sm:px-2.5 sm:text-[11px]">
                                                                        Read only
                                                                    </span>
                                                                )}

                                                                {report.severity && (
                                                                    <span className={`shrink-0 rounded-full px-2 py-1 text-[10px] font-semibold capitalize sm:px-2.5 sm:text-[11px] ${severityClasses(report.severity)}`}>
                                                                        {report.severity}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <div className="mt-2 flex min-w-0 flex-wrap items-center gap-x-3 gap-y-1.5 text-xs text-slate-500 dark:text-slate-400 sm:gap-x-4 sm:text-sm">
                                                                <span className="max-w-full wrap-break-word">
                                                                    <span className="font-medium text-slate-700 dark:text-slate-300">Reported by:</span> {reporter}
                                                                </span>

                                                                {report.category && (
                                                                    <span className="max-w-full wrap-break-word">
                                                                        <span className="font-medium text-slate-700 dark:text-slate-300">Category:</span> {report.category.category_name}
                                                                    </span>
                                                                )}

                                                                {report.location && (
                                                                    <span className="max-w-full wrap-break-word">
                                                                        <span className="font-medium text-slate-700 dark:text-slate-300">Location:</span> {report.location}
                                                                    </span>
                                                                )}
                                                            </div>

                                                            {report.description && (
                                                                <p className="mt-2 line-clamp-2 wrap-break-word text-xs leading-5 text-slate-500 dark:text-slate-400 sm:text-sm">
                                                                    {report.description}
                                                                </p>
                                                            )}

                                                            {(currentLevel || assignee) && (
                                                                <div className="mt-3 flex min-w-0 flex-wrap gap-2">
                                                                    {assignee && (
                                                                        <span className="max-w-full wrap-break-word rounded-lg bg-slate-100 px-2.5 py-1.5 text-[11px] font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                                                            Current handler: {assignee}
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>

                                                    <div className="flex w-full shrink-0 items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400 xl:w-auto xl:border-0 xl:pt-0 xl:text-right">
                                                        <div className="min-w-0">
                                                            {report.report_code && <p className="mb-1 break-all font-mono text-[10px] font-medium text-slate-400 sm:text-[11px]">{report.report_code}</p>}
                                                            <p className="font-medium text-slate-700 dark:text-slate-300">{formatDate(report.created_at)}</p>
                                                            <p className="mt-0.5 text-slate-400">{formatTime(report.created_at)}</p>
                                                        </div>

                                                        <ArrowRight className="h-4 w-4 shrink-0 text-slate-300 transition group-hover:translate-x-0.5 group-hover:text-slate-500 dark:text-slate-600 dark:group-hover:text-slate-300 xl:block" />
                                                    </div>
                                                </div>
                                            </Link>
                                        );
                                    }) : (
                                        <div className="px-5 py-14 text-center sm:px-6 sm:py-16">
                                            <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800">
                                                <FileWarning className="h-6 w-6 text-slate-400" />
                                            </div>
                                            <h3 className="text-base font-semibold text-slate-700 dark:text-slate-200">No reports found</h3>
                                            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">No cases match the current search and filters.</p>
                                        </div>
                                    )}
                                </div>

                                {/* Pagination */}
                                {reports.links.length > 3 && (
                                    <div className="overflow-x-auto border-t border-slate-200 bg-white px-3 py-4 dark:border-slate-800 dark:bg-slate-900 sm:px-5">
                                        <Pagination links={reports.links} meta={{ from: reports.from, to: reports.to, total: reports.total }} />
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
