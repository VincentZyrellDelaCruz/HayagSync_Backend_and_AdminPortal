import Pagination from '@/components/ui/pagination';
import Modal from '@/components/ui/modal';
import AppLayout from '@/layouts/app-layout';
import { Paginated, type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Clock3, Eye, History, Laptop, Shield, ShieldAlert, SlidersHorizontal, UserRound, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface LogUser {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
}

interface ActivityLog {
    id: string;
    user_id?: string | null;
    action_type: string;
    description: string;
    module: string;
    record_id?: string | null;
    ip_address?: string | null;
    created_at: string;
    user?: LogUser | null;
}

interface LoginHistory {
    id: string;
    user_id?: string | null;
    device_name?: string | null;
    browser?: string | null;
    ip_address?: string | null;
    location?: string | null;
    login_time: string;
    user?: LogUser | null;
}

interface SecurityEvent {
    id: string;
    user_id?: string | null;
    severity: string;
    event_type: string;
    description: string;
    ip_address?: string | null;
    location?: string | null;
    status: string;
    resolved_at?: string | null;
    resolved_by?: string | null;
    created_at: string;
    updated_at?: string | null;
    user?: LogUser | null;
    admin?: {
        user?: LogUser | null;
    } | null;
}

interface Summary {
    activity: number;
    recent_logins: number;
    open_security_events: number;
    critical_security_events: number;
}

interface SecurityCenterProps {
    activityLogs: Paginated<ActivityLog>;
    loginHistory: Paginated<LoginHistory>;
    securityEvents: Paginated<SecurityEvent>;
    summary: Summary;
    tab: string;
    search: string;
    severity?: string | null;
    status?: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Security Center',
        href: '/admin/security',
    },
];

const tabs = [
    {
        value: 'activity',
        label: 'Activity Logs',
        icon: History,
    },
    {
        value: 'logins',
        label: 'Login History',
        icon: Laptop,
    },
    {
        value: 'security',
        label: 'Security Events',
        icon: ShieldAlert,
    },
];

const severityOptions = [
    {
        value: 'low',
        label: 'Low',
    },
    {
        value: 'medium',
        label: 'Medium',
    },
    {
        value: 'high',
        label: 'High',
    },
    {
        value: 'critical',
        label: 'Critical',
    },
];

const statusOptions = [
    {
        value: 'open',
        label: 'Open',
    },
    {
        value: 'resolved',
        label: 'Resolved',
    },
];

export default function SecurityCenter({ activityLogs, loginHistory, securityEvents, summary, tab, search, severity, status }: SecurityCenterProps) {
    const [activeTab, setActiveTab] = useState( tab || 'activity');

    const [searchValue, setSearchValue] = useState( search ?? '');

    const [severityValue, setSeverityValue] = useState( severity ?? '');

    const [statusValue, setStatusValue] = useState( status ?? '');

    const [selectedActivity, setSelectedActivity] = useState<ActivityLog | null>(null);

    const [selectedLogin, setSelectedLogin] = useState<LoginHistory | null>(null);

    const [selectedSecurityEvent, setSelectedSecurityEvent] = useState<SecurityEvent | null>(null);

    const [isResolving, setIsResolving] = useState(false);

    const initialized = useRef(false);

    const formatDateTime = (value?: string | null) => {
        if (!value) {
            return 'N/A';
        }

        const date = new Date(value);

        if (Number.isNaN(date.getTime())) {
            return value;
        }

        return date.toLocaleString('en-US', {
            month: 'short',
            day: '2-digit',
            year: 'numeric',
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        });
    };

    const fullName = (user?: LogUser | null) => {
        if (!user) {
            return 'Unknown User';
        }

        return `${user.last_name}, ${user.first_name}`;
    };

    const getSeverityClass = (value: string) => {
        switch (value.toLowerCase()) {
            case 'critical':
                return 'bg-red-100 text-red-700 ring-1 ring-red-200';

            case 'high':
                return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';

            case 'medium':
                return 'bg-yellow-100 text-yellow-700 ring-1 ring-yellow-200';

            case 'low':
                return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';

            default:
                return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
        }
    };

    const getStatusClass = (value: string) => {
        return value.toLowerCase() === 'resolved'
            ? 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200'
            : 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
    };

    // APPLY FILTERS
    const applyFilters = (
        nextSearch: string,
        nextTab: string,
        nextSeverity = severityValue,
        nextStatus = statusValue,
    ) => {
        const params: Record<string, string> = {
            tab: nextTab,
        };

        if (nextSearch.trim() !== '') {
            params.search = nextSearch.trim();
        }

        if (nextTab === 'security' && nextSeverity !== '') {
            params.severity = nextSeverity;
        }

        if (nextTab === 'security' && nextStatus !== '') {
            params.status = nextStatus;
        }

        router.get(route('web.admin.security.index'), params, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    // AUTOMATIC SEARCH
    useEffect(() => {
        if (!initialized.current) {
            initialized.current = true;
            return;
        }

        const timeout = window.setTimeout(() => {
            applyFilters(searchValue, activeTab, severityValue, statusValue);
        }, 400);

        return () => window.clearTimeout(timeout);
    }, [searchValue]);

    const handleTabChange = (
        value: string,
    ) => {
        setActiveTab(value);

        applyFilters(searchValue, value,
            value === 'security'
                ? severityValue
                : '',
            value === 'security'
                ? statusValue
                : '',
        );
    };

    const handleSeverityChange = (value: string) => {
        setSeverityValue(value);

        applyFilters(searchValue, 'security', value, statusValue);
    };

    const handleStatusChange = (value: string) => {
        setStatusValue(value);

        applyFilters(searchValue, 'security', severityValue, value);
    };

    const clearFilters = () => {
        const hadSearch = searchValue.trim() !== '';
        setSearchValue('');
        setSeverityValue('');
        setStatusValue('');
        if (!hadSearch) applyFilters('', activeTab, '', '');
    };

    const resolveSecurityEvent = () => {
        if (!selectedSecurityEvent) {
            return;
        }

        setIsResolving(true);

        router.patch(route('web.admin.security.events.resolve', selectedSecurityEvent.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    setSelectedSecurityEvent(null);
                },
                onFinish: () => {
                    setIsResolving(false);
                },
            },
        );
    };

    const hasFilters = searchValue.trim() !== '' || severityValue !== '' || statusValue !== '';

    const currentTab = tabs.find(
            (item) => item.value === activeTab,
        ) ?? tabs[0];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Security Center" />

            <div className="min-h-full bg-slate-50/60">
                <div className="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    {/* Page Header */}
                    <div className="mb-5 sm:mb-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-600 dark:bg-red-500 text-white">
                                <Shield className="h-5 w-5" />
                            </div>

                            <div className="min-w-0">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                    Security Center
                                </h1>

                                <p className="text-sm text-slate-500">
                                    Monitor activity, authentication history, and security events
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium text-slate-500">
                                        Activity Logs
                                    </p>

                                    <p className="mt-1 text-xl font-bold text-slate-900">
                                        {summary.activity}
                                    </p>
                                </div>

                                <History className="h-5 w-5 text-slate-400" />
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium text-slate-500">
                                        Recent Logins
                                    </p>

                                    <p className="mt-1 text-xl font-bold text-slate-900">
                                        {summary.recent_logins}
                                    </p>

                                    <p className="mt-0.5 text-[11px] text-slate-400">
                                        Last 7 days
                                    </p>
                                </div>

                                <Clock3 className="h-5 w-5 text-slate-400" />
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium text-slate-500">
                                        Open Security Events
                                    </p>

                                    <p className="mt-1 text-xl font-bold text-amber-600">
                                        {summary.open_security_events}
                                    </p>
                                </div>

                                <AlertTriangle className="h-5 w-5 text-amber-500" />
                            </div>
                        </div>

                        <div className="rounded-2xl border border-red-100 bg-white p-4 shadow-sm">
                            <div className="flex items-start justify-between gap-3">
                                <div>
                                    <p className="text-xs font-medium text-slate-500">
                                        Critical Events
                                    </p>

                                    <p className="mt-1 text-xl font-bold text-red-600">
                                        {summary.critical_security_events}
                                    </p>
                                </div>

                                <ShieldAlert className="h-5 w-5 text-red-500" />
                            </div>
                        </div>
                    </div>

                    {/* Search & Filters */}
                    <div className="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div className="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div className="flex min-w-0 items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4 shrink-0 text-slate-500" />

                                <span className="text-sm font-semibold text-slate-700">
                                    Search & Filters
                                </span>
                            </div>

                            {hasFilters && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="inline-flex w-fit items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                >
                                    <X className="h-3.5 w-3.5" />
                                    Clear
                                </button>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-12">

                            {/* Search */}
                            <div className="relative md:col-span-7">
                                <SearchIcon />

                                <input
                                    type="text"
                                    value={searchValue}
                                    onChange={(e) => setSearchValue(e.target.value)}
                                    placeholder="Search users, actions, IP addresses, devices..."
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                            </div>

                            {activeTab === 'security' && (
                                <>
                                    {/* Severity */}
                                    <div className="md:col-span-2.5">
                                        <select
                                            value={severityValue}
                                            onChange={(e) => handleSeverityChange(e.target.value)}
                                            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">
                                                All Severity
                                            </option>

                                            {severityOptions.map(
                                                (option) => (
                                                    <option
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {option.label}
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </div>

                                    {/* Status */}
                                    <div className="md:col-span-2.5">
                                        <select
                                            value={statusValue}
                                            onChange={(e) =>
                                                handleStatusChange(
                                                    e.target.value,
                                                )
                                            }
                                            className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        >
                                            <option value="">
                                                All Status
                                            </option>

                                            {statusOptions.map(
                                                (option) => (
                                                    <option
                                                        key={
                                                            option.value
                                                        }
                                                        value={
                                                            option.value
                                                        }
                                                    >
                                                        {
                                                            option.label
                                                        }
                                                    </option>
                                                ),
                                            )}
                                        </select>
                                    </div>
                                </>
                            )}
                        </div>

                        {hasFilters && (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {searchValue && (
                                    <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        Search: {searchValue}
                                    </span>
                                )}

                                {severityValue && (
                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Severity: {severityValue}
                                    </span>
                                )}

                                {statusValue && (
                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        Status: {statusValue}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Tabs */}
                    <div className="mb-5 overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex min-w-max border-b border-slate-200 px-2 pt-2">
                            {tabs.map((item) => {
                                const Icon = item.icon;
                                const isActive =
                                    item.value ===
                                    activeTab;

                                return (
                                    <button
                                        key={item.value}
                                        type="button"
                                        onClick={() =>
                                            handleTabChange(
                                                item.value,
                                            )
                                        }
                                        className={`inline-flex items-center gap-2 rounded-t-xl px-4 py-3 text-sm font-medium transition ${
                                            isActive
                                                ? 'border-b-2 border-blue-600 text-blue-600'
                                                : 'border-b-2 border-transparent text-slate-500 hover:text-slate-700'
                                        }`}
                                    >
                                        <Icon className="h-4 w-4" />
                                        {item.label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>

                    {/* Content */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {/* Result Header */}
                        <div className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-800">
                                    {currentTab.label}
                                </h2>

                                <p className="text-xs text-slate-500">
                                    {activeTab ===
                                    'activity'
                                        ? activityLogs.total
                                        : activeTab ===
                                          'logins'
                                          ? loginHistory.total
                                          : securityEvents.total}{' '}
                                    records
                                </p>
                            </div>
                        </div>

                        {/* Activity Logs */}
                        {activeTab === 'activity' && (
                            <>
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Date
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    User
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Module
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    IP Address
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {activityLogs.data.length >
                                            0 ? (
                                                activityLogs.data.map(
                                                    (log) => (
                                                        <tr
                                                            key={
                                                                log.id
                                                            }
                                                            className="transition hover:bg-slate-50"
                                                        >
                                                            <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                                {formatDateTime(
                                                                    log.created_at,
                                                                )}
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm font-medium text-slate-800">
                                                                    {fullName(
                                                                        log.user,
                                                                    )}
                                                                </p>

                                                                <p className="text-xs text-slate-500">
                                                                    {log
                                                                        .user
                                                                        ?.email ??
                                                                        'N/A'}
                                                                </p>
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm font-medium text-slate-700">
                                                                    {
                                                                        log.action_type
                                                                    }
                                                                </p>

                                                                <p className="max-w-xs truncate text-xs text-slate-500">
                                                                    {
                                                                        log.description
                                                                    }
                                                                </p>
                                                            </td>

                                                            <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                                {
                                                                    log.module
                                                                }
                                                            </td>

                                                            <td className="whitespace-nowrap px-5 py-4 font-mono text-xs text-slate-500">
                                                                {
                                                                    log.ip_address ??
                                                                    'N/A'
                                                                }
                                                            </td>

                                                            <td className="px-5 py-4 text-right">
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        setSelectedActivity(
                                                                            log,
                                                                        )
                                                                    }
                                                                    className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200"
                                                                >
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                    View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <EmptyRow
                                                    colSpan={6}
                                                    text="No activity records found."
                                                />
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="divide-y divide-slate-100 md:hidden">
                                    {activityLogs.data.length >
                                    0 ? (
                                        activityLogs.data.map(
                                            (log) => (
                                                <button
                                                    key={
                                                        log.id
                                                    }
                                                    type="button"
                                                    onClick={() =>
                                                        setSelectedActivity(
                                                            log,
                                                        )
                                                    }
                                                    className="block w-full p-4 text-left transition hover:bg-slate-50"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="text-sm font-semibold text-slate-800">
                                                                {
                                                                    log.action_type
                                                                }
                                                            </p>

                                                            <p className="mt-1 truncate text-xs text-slate-500">
                                                                {fullName(
                                                                    log.user,
                                                                )}
                                                            </p>
                                                        </div>

                                                        <span className="shrink-0 text-xs text-slate-400">
                                                            {formatDateTime(
                                                                log.created_at,
                                                            )}
                                                        </span>
                                                    </div>

                                                    <p className="mt-2 line-clamp-2 text-xs text-slate-600">
                                                        {
                                                            log.description
                                                        }
                                                    </p>
                                                </button>
                                            ),
                                        )
                                    ) : (
                                        <EmptyMobile text="No activity records found." />
                                    )}
                                </div>

                                {activityLogs.links.length >
                                    3 && (
                                    <PaginationContainer>
                                        <Pagination
                                            links={
                                                activityLogs.links
                                            }
                                            meta={{
                                                from: activityLogs.from,
                                                to: activityLogs.to,
                                                total: activityLogs.total,
                                            }}
                                        />
                                    </PaginationContainer>
                                )}
                            </>
                        )}

                        {/* Login History */}
                        {activeTab === 'logins' && (
                            <>
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Login Time
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    User
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Device
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Browser
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    IP / Location
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {loginHistory.data.length >
                                            0 ? (
                                                loginHistory.data.map(
                                                    (login) => (
                                                        <tr
                                                            key={
                                                                login.id
                                                            }
                                                            className="transition hover:bg-slate-50"
                                                        >
                                                            <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                                {formatDateTime(
                                                                    login.login_time,
                                                                )}
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm font-medium text-slate-800">
                                                                    {fullName(
                                                                        login.user,
                                                                    )}
                                                                </p>

                                                                <p className="text-xs text-slate-500">
                                                                    {login
                                                                        .user
                                                                        ?.email ??
                                                                        'N/A'}
                                                                </p>
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-700">
                                                                    {login.device_name ??
                                                                        'Unknown'}
                                                                </p>
                                                            </td>

                                                            <td className="px-5 py-4 text-sm text-slate-600">
                                                                {login.browser ??
                                                                    'Unknown'}
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="font-mono text-xs text-slate-600">
                                                                    {login.ip_address ??
                                                                        'N/A'}
                                                                </p>

                                                                <p className="mt-0.5 max-w-xs truncate text-xs text-slate-400">
                                                                    {login.location ??
                                                                        'Location unavailable'}
                                                                </p>
                                                            </td>

                                                            <td className="px-5 py-4 text-right">
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        setSelectedLogin(
                                                                            login,
                                                                        )
                                                                    }
                                                                    className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200"
                                                                >
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                    View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <EmptyRow
                                                    colSpan={6}
                                                    text="No login history found."
                                                />
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="divide-y divide-slate-100 md:hidden">
                                    {loginHistory.data.length >
                                    0 ? (
                                        loginHistory.data.map(
                                            (login) => (
                                                <button
                                                    key={
                                                        login.id
                                                    }
                                                    type="button"
                                                    onClick={() =>
                                                        setSelectedLogin(
                                                            login,
                                                        )
                                                    }
                                                    className="block w-full p-4 text-left transition hover:bg-slate-50"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="flex min-w-0 items-center gap-3">
                                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                                                                <Laptop className="h-4 w-4" />
                                                            </div>

                                                            <div className="min-w-0">
                                                                <p className="truncate text-sm font-semibold text-slate-800">
                                                                    {fullName(
                                                                        login.user,
                                                                    )}
                                                                </p>

                                                                <p className="truncate text-xs text-slate-500">
                                                                    {login.device_name ??
                                                                        'Unknown device'}
                                                                </p>
                                                            </div>
                                                        </div>

                                                        <span className="shrink-0 text-xs text-slate-400">
                                                            {formatDateTime(
                                                                login.login_time,
                                                            )}
                                                        </span>
                                                    </div>

                                                    <div className="mt-3 flex flex-wrap gap-2 text-xs">
                                                        <span className="rounded-lg bg-slate-50 px-2.5 py-1.5 text-slate-600">
                                                            {login.browser ??
                                                                'Unknown browser'}
                                                        </span>

                                                        <span className="rounded-lg bg-slate-50 px-2.5 py-1.5 font-mono text-slate-600">
                                                            {login.ip_address ??
                                                                'N/A'}
                                                        </span>
                                                    </div>
                                                </button>
                                            ),
                                        )
                                    ) : (
                                        <EmptyMobile text="No login history found." />
                                    )}
                                </div>

                                {loginHistory.links.length >
                                    3 && (
                                    <PaginationContainer>
                                        <Pagination
                                            links={
                                                loginHistory.links
                                            }
                                            meta={{
                                                from: loginHistory.from,
                                                to: loginHistory.to,
                                                total: loginHistory.total,
                                            }}
                                        />
                                    </PaginationContainer>
                                )}
                            </>
                        )}

                        {/* Security Events */}
                        {activeTab === 'security' && (
                            <>
                                <div className="hidden overflow-x-auto md:block">
                                    <table className="min-w-full divide-y divide-slate-200">
                                        <thead className="bg-slate-50">
                                            <tr>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Severity
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Event
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    User
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Date
                                                </th>
                                                <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Status
                                                </th>
                                                <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody className="divide-y divide-slate-100">
                                            {securityEvents.data.length >
                                            0 ? (
                                                securityEvents.data.map(
                                                    (event) => (
                                                        <tr
                                                            key={
                                                                event.id
                                                            }
                                                            className="transition hover:bg-slate-50"
                                                        >
                                                            <td className="px-5 py-4">
                                                                <span
                                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${getSeverityClass(
                                                                        event.severity,
                                                                    )}`}
                                                                >
                                                                    {
                                                                        event.severity
                                                                    }
                                                                </span>
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm font-medium text-slate-800">
                                                                    {
                                                                        event.event_type
                                                                    }
                                                                </p>

                                                                <p className="max-w-xs truncate text-xs text-slate-500">
                                                                    {
                                                                        event.description
                                                                    }
                                                                </p>
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-700">
                                                                    {fullName(
                                                                        event.user,
                                                                    )}
                                                                </p>

                                                                <p className="text-xs text-slate-500">
                                                                    {event
                                                                        .user
                                                                        ?.email ??
                                                                        'N/A'}
                                                                </p>
                                                            </td>

                                                            <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                                {formatDateTime(
                                                                    event.created_at,
                                                                )}
                                                            </td>

                                                            <td className="px-5 py-4">
                                                                <span
                                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${getStatusClass(
                                                                        event.status,
                                                                    )}`}
                                                                >
                                                                    {
                                                                        event.status
                                                                    }
                                                                </span>
                                                            </td>

                                                            <td className="px-5 py-4 text-right">
                                                                <button
                                                                    type="button"
                                                                    onClick={() =>
                                                                        setSelectedSecurityEvent(
                                                                            event,
                                                                        )
                                                                    }
                                                                    className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200"
                                                                >
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                    View
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    ),
                                                )
                                            ) : (
                                                <EmptyRow
                                                    colSpan={6}
                                                    text="No security events found."
                                                />
                                            )}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="divide-y divide-slate-100 md:hidden">
                                    {securityEvents.data.length >
                                    0 ? (
                                        securityEvents.data.map(
                                            (event) => (
                                                <button
                                                    key={
                                                        event.id
                                                    }
                                                    type="button"
                                                    onClick={() =>
                                                        setSelectedSecurityEvent(
                                                            event,
                                                        )
                                                    }
                                                    className="block w-full p-4 text-left transition hover:bg-slate-50"
                                                >
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <div className="flex flex-wrap items-center gap-2">
                                                                <span
                                                                    className={`rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ${getSeverityClass(
                                                                        event.severity,
                                                                    )}`}
                                                                >
                                                                    {
                                                                        event.severity
                                                                    }
                                                                </span>

                                                                <span
                                                                    className={`rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ${getStatusClass(
                                                                        event.status,
                                                                    )}`}
                                                                >
                                                                    {
                                                                        event.status
                                                                    }
                                                                </span>
                                                            </div>

                                                            <p className="mt-2 truncate text-sm font-semibold text-slate-800">
                                                                {
                                                                    event.event_type
                                                                }
                                                            </p>

                                                            <p className="mt-1 truncate text-xs text-slate-500">
                                                                {fullName(
                                                                    event.user,
                                                                )}
                                                            </p>
                                                        </div>

                                                        <span className="shrink-0 text-xs text-slate-400">
                                                            {formatDateTime(
                                                                event.created_at,
                                                            )}
                                                        </span>
                                                    </div>

                                                    <p className="mt-3 line-clamp-2 text-xs text-slate-600">
                                                        {
                                                            event.description
                                                        }
                                                    </p>
                                                </button>
                                            ),
                                        )
                                    ) : (
                                        <EmptyMobile text="No security events found." />
                                    )}
                                </div>

                                {securityEvents.links.length >
                                    3 && (
                                    <PaginationContainer>
                                        <Pagination
                                            links={
                                                securityEvents.links
                                            }
                                            meta={{
                                                from: securityEvents.from,
                                                to: securityEvents.to,
                                                total: securityEvents.total,
                                            }}
                                        />
                                    </PaginationContainer>
                                )}
                            </>
                        )}
                    </div>
                </div>
            </div>

            {/* Activity Log Modal */}
            <Modal
                isOpen={!!selectedActivity}
                onClose={() =>
                    setSelectedActivity(null)
                }
                title="Activity Log Details"
                description="Complete information about this recorded action."
                size="lg"
            >
                {selectedActivity && (
                    <div className="space-y-4">
                        <DetailRow
                            label="Action"
                            value={
                                selectedActivity.action_type
                            }
                        />

                        <DetailRow
                            label="User"
                            value={fullName(
                                selectedActivity.user,
                            )}
                        />

                        <DetailRow
                            label="Email"
                            value={
                                selectedActivity.user
                                    ?.email ?? 'N/A'
                            }
                        />

                        <DetailRow
                            label="Module"
                            value={
                                selectedActivity.module
                            }
                        />

                        <DetailRow
                            label="Record ID"
                            value={
                                selectedActivity.record_id ??
                                'N/A'
                            }
                        />

                        <DetailRow
                            label="IP Address"
                            value={
                                selectedActivity.ip_address ??
                                'N/A'
                            }
                        />

                        <DetailRow
                            label="Date & Time"
                            value={formatDateTime(
                                selectedActivity.created_at,
                            )}
                        />

                        <div>
                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Description
                            </p>

                            <div className="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                                {
                                    selectedActivity.description
                                }
                            </div>
                        </div>
                    </div>
                )}
            </Modal>

            {/* Login History Modal */}
            <Modal
                isOpen={!!selectedLogin}
                onClose={() =>
                    setSelectedLogin(null)
                }
                title="Login History Details"
                description="Authentication and access information recorded for this login."
                size="lg"
            >
                {selectedLogin && (
                    <div className="space-y-4">
                        <DetailRow
                            label="User"
                            value={fullName(
                                selectedLogin.user,
                            )}
                        />

                        <DetailRow
                            label="Email"
                            value={
                                selectedLogin.user
                                    ?.email ?? 'N/A'
                            }
                        />

                        <DetailRow
                            label="Device"
                            value={
                                selectedLogin.device_name ??
                                'Unknown'
                            }
                        />

                        <DetailRow
                            label="Browser"
                            value={
                                selectedLogin.browser ??
                                'Unknown'
                            }
                        />

                        <DetailRow
                            label="IP Address"
                            value={
                                selectedLogin.ip_address ??
                                'N/A'
                            }
                        />

                        <DetailRow
                            label="Location"
                            value={
                                selectedLogin.location ??
                                'Location unavailable'
                            }
                        />

                        <DetailRow
                            label="Login Time"
                            value={formatDateTime(
                                selectedLogin.login_time,
                            )}
                        />
                    </div>
                )}
            </Modal>

            {/* Security Event Modal */}
            <Modal
                isOpen={!!selectedSecurityEvent}
                onClose={() =>
                    setSelectedSecurityEvent(null)
                }
                title="Security Event Details"
                description="Review the event and determine whether administrative action is required."
                size="lg"
                footer={
                    selectedSecurityEvent &&
                    selectedSecurityEvent.status.toLowerCase() !==
                        'resolved' ? (
                        <>
                            <button
                                type="button"
                                onClick={() =>
                                    setSelectedSecurityEvent(
                                        null,
                                    )
                                }
                                className="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                            >
                                Close
                            </button>

                            <button
                                type="button"
                                onClick={
                                    resolveSecurityEvent
                                }
                                disabled={isResolving}
                                className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                <CheckCircle2 className="h-4 w-4" />

                                {isResolving
                                    ? 'Resolving...'
                                    : 'Mark as Resolved'}
                            </button>
                        </>
                    ) : (
                        <button
                            type="button"
                            onClick={() =>
                                setSelectedSecurityEvent(
                                    null,
                                )
                            }
                            className="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Close
                        </button>
                    )
                }
            >
                {selectedSecurityEvent && (
                    <div className="space-y-4">
                        <div className="flex flex-wrap gap-2">
                            <span
                                className={`rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${getSeverityClass(
                                    selectedSecurityEvent.severity,
                                )}`}
                            >
                                {
                                    selectedSecurityEvent.severity
                                }
                            </span>

                            <span
                                className={`rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${getStatusClass(
                                    selectedSecurityEvent.status,
                                )}`}
                            >
                                {
                                    selectedSecurityEvent.status
                                }
                            </span>
                        </div>

                        <DetailRow
                            label="Event Type"
                            value={
                                selectedSecurityEvent.event_type
                            }
                        />

                        <DetailRow
                            label="Affected User"
                            value={fullName(
                                selectedSecurityEvent.user,
                            )}
                        />

                        <DetailRow
                            label="Email"
                            value={
                                selectedSecurityEvent.user
                                    ?.email ?? 'N/A'
                            }
                        />

                        <DetailRow
                            label="IP Address"
                            value={
                                selectedSecurityEvent.ip_address ??
                                'N/A'
                            }
                        />

                        <DetailRow
                            label="Location"
                            value={
                                selectedSecurityEvent.location ??
                                'Location unavailable'
                            }
                        />

                        <DetailRow
                            label="Occurred"
                            value={formatDateTime(
                                selectedSecurityEvent.created_at,
                            )}
                        />

                        {selectedSecurityEvent.resolved_at && (
                            <DetailRow
                                label="Resolved At"
                                value={formatDateTime(
                                    selectedSecurityEvent.resolved_at,
                                )}
                            />
                        )}

                        {selectedSecurityEvent.admin
                            ?.user && (
                            <DetailRow
                                label="Resolved By"
                                value={fullName(
                                    selectedSecurityEvent
                                        .admin.user,
                                )}
                            />
                        )}

                        <div>
                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                Description
                            </p>

                            <div className="rounded-xl bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                                {
                                    selectedSecurityEvent.description
                                }
                            </div>
                        </div>
                    </div>
                )}
            </Modal>
        </AppLayout>
    );
}

function SearchIcon() {
    return (
        <svg
            className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
        >
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.5-3.5" />
        </svg>
    );
}

function EmptyRow({
    colSpan,
    text,
}: {
    colSpan: number;
    text: string;
}) {
    return (
        <tr>
            <td
                colSpan={colSpan}
                className="px-5 py-12 text-center"
            >
                <Shield className="mx-auto mb-3 h-8 w-8 text-slate-300" />

                <p className="text-sm font-medium text-slate-700">
                    {text}
                </p>
            </td>
        </tr>
    );
}

function EmptyMobile({
    text,
}: {
    text: string;
}) {
    return (
        <div className="px-5 py-12 text-center">
            <Shield className="mx-auto mb-3 h-8 w-8 text-slate-300" />

            <p className="text-sm font-medium text-slate-700">
                {text}
            </p>
        </div>
    );
}

function PaginationContainer({
    children,
}: {
    children: React.ReactNode;
}) {
    return (
        <div className="overflow-x-auto border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
            {children}
        </div>
    );
}

function DetailRow({
    label,
    value,
}: {
    label: string;
    value: string;
}) {
    return (
        <div>
            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                {label}
            </p>

            <p className="wrap-break-word text-sm text-slate-700">
                {value}
            </p>
        </div>
    );
}
