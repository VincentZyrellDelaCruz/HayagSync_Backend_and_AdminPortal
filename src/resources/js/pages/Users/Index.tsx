import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { Paginated, Staff, User, type BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    Search,
    SlidersHorizontal,
    UserRound,
    X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

declare function route(
    name: string,
    params?: Record<string, unknown> | number | string,
): string;

interface UsersIndexProps {
    users: Paginated<User>;
    filter: string;
    search?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Users',
        href: '/users',
    },
];

export default function UsersIndex({ users, filter, search, }: UsersIndexProps) {

    const { auth } = usePage().props as any;

    // Initial Filter
    const filterOptions = [
        {
            value: 'parent_guardian',
            label: 'Parent / Guardian',
        },
    ];

    if (auth?.user?.staff?.latest_position?.position_name  !== 'Teacher') {
        filterOptions.push(
            {
                value: 'staff',
                label: 'All Staff',
            },
            {
                value: 'teacher',
                label: 'Teachers',
            },
            {
                value: 'principal_ministro',
                label: 'Principal & Ministrong Tagasubaybay',
            },
            {
                value: 'osd_officer',
                label: 'OSD Officers',
            },
        );
    };

    if (auth?.user?.staff?.is_admin) {
        filterOptions.push({ value: 'admin', label: 'Administrators' });
    };

    const [searchValue, setSearchValue] = useState(search ?? '');
    const [filterValue, setFilterValue] = useState(filter ?? 'parent_guardian');
    const [isFiltering, setIsFiltering] = useState(false);

    const initialized = useRef(false);

    const selectedFilterLabel = filterOptions.find((option) => option.value === filterValue)?.label ?? 'Users';

    const isParentGuardian =
        filterValue === 'parent_guardian';

    /*
    |--------------------------------------------------------------------------
    | Apply Filters
    |--------------------------------------------------------------------------
    |
    | Sends the current search and selected user type to Laravel.
    |
    */
    const applyFilters = (nextSearch: string, nextFilter: string,) => {
        const params: Record<string, string> = {filter: nextFilter};

        if (nextSearch.trim() !== '') {
            params.search = nextSearch.trim();
        }

        setIsFiltering(true);

        router.get(
            route('web.users.index'),
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    };

    // Automatic Search. Waits 400ms after the user stops typing before sending the request.
    useEffect(() => {
        if (!initialized.current) {
            initialized.current = true;
            return;
        }

        const timeout = window.setTimeout(() => {
            applyFilters(
                searchValue,
                filterValue,
            );
        }, 400);

        return () => window.clearTimeout(timeout);
    }, [searchValue]);

    // User type filter
    const handleFilterChange = (value: string) => {
        setFilterValue(value);

        applyFilters(
            searchValue,
            value,
        );
    };

    const clearFilters = () => {
        setSearchValue('');
        setFilterValue('parent_guardian');

        applyFilters('', 'parent_guardian');
    };

    const getLatestPosition = (
        staff?: Staff | null,
    ): string => {
        if (!staff?.positions?.length) {
            return 'Unknown';
        }

        const sortedPositions = [
            ...staff.positions,
        ].sort((a, b) => {
            const dateA = a.pivot?.assigned_at
                ? new Date(
                    a.pivot.assigned_at,
                ).getTime()
                : 0;

            const dateB = b.pivot?.assigned_at
                ? new Date(
                    b.pivot.assigned_at,
                ).getTime()
                : 0;

            return dateB - dateA;
        });

        return (
            sortedPositions[0]?.position_name ??
            'Unknown'
        );
    };

    const hasFilters =
        searchValue.trim() !== '' ||
        filterValue !== 'parent_guardian';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="min-h-full bg-slate-50/60">
                <div className="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    {/* Page Header */}
                    <div className="mb-5 sm:mb-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                <UserRound className="h-5 w-5" />
                            </div>

                            <div className="min-w-0">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                    Parent / Staff Directory
                                </h1>

                                <p className="text-sm text-slate-500">
                                    Search and view registered parents/guardians and staff users
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* Search & Filters */}
                    <div className="mb-5 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                        <div className="mb-4 flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <SlidersHorizontal className="h-4 w-4 text-slate-500" />

                                <span className="text-sm font-semibold text-slate-700">
                                    Search & Filters
                                </span>
                            </div>

                            {hasFilters && (
                                <button
                                    type="button"
                                    onClick={clearFilters}
                                    className="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-700"
                                >
                                    <X className="h-3.5 w-3.5" />
                                    Clear
                                </button>
                            )}
                        </div>

                        <div className="grid grid-cols-1 gap-3 md:grid-cols-12">

                            {/* Search */}
                            <div className="relative md:col-span-8">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                <input
                                    id="search"
                                    type="text"
                                    value={searchValue}
                                    onChange={(e) =>
                                        setSearchValue(
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Search by name or email..."
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-10 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />

                                {isFiltering && (
                                    <div className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />
                                    </div>
                                )}
                            </div>

                            {/* User Type */}
                            <div className="md:col-span-4">
                                <select
                                    id="filter"
                                    name="filter"
                                    value={filterValue}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            e.target.value,
                                        )
                                    }
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    {filterOptions.map(
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
                        </div>

                        {/* Active Filter Summary */}
                        {hasFilters && (
                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                {searchValue && (
                                    <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        Search: {searchValue}
                                    </span>
                                )}

                                {filterValue !==
                                    'parent_guardian' && (
                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {selectedFilterLabel}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>

                    {/* Results */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        {/* Result Header */}
                        <div className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-800">
                                    {selectedFilterLabel}
                                </h2>

                                <p className="text-xs text-slate-500">
                                    {users.total}{' '}
                                    {users.total === 1
                                        ? 'user'
                                        : 'users'}{' '}
                                    found
                                </p>
                            </div>

                            {users.from !== null &&
                                users.to !== null && (
                                    <p className="text-xs text-slate-500">
                                        Showing{' '}
                                        {users.from}–
                                        {users.to} of{' '}
                                        {users.total}
                                    </p>
                                )}
                        </div>

                        {/* Desktop / Tablet Table */}
                        <div className="hidden overflow-x-auto md:block">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Name
                                        </th>

                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Email
                                        </th>

                                        {isParentGuardian ? (
                                            <>
                                                <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Parent Code
                                                </th>

                                                <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Occupation
                                                </th>
                                            </>
                                        ) : (
                                            <>
                                                <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Staff No.
                                                </th>

                                                <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Position
                                                </th>

                                                <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                    Department
                                                </th>
                                            </>
                                        )}

                                        <th className="whitespace-nowrap px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {users.data.length > 0 ? (
                                        users.data.map(
                                            (user) => (
                                                <tr
                                                    key={user.id}
                                                    className="group transition hover:bg-slate-50"
                                                >
                                                    {/* Name */}
                                                    <td className="px-5 py-4">
                                                        <Link
                                                            href={route(
                                                                'web.users.show',
                                                                user.id,
                                                            )}
                                                            className="block"
                                                        >
                                                            <p className="text-sm font-medium text-slate-800 transition group-hover:text-blue-600">
                                                                {
                                                                    user.last_name
                                                                }
                                                                ,{' '}
                                                                {
                                                                    user.first_name
                                                                }
                                                            </p>
                                                        </Link>
                                                    </td>

                                                    {/* Email */}
                                                    <td className="px-5 py-4">
                                                        <p className="max-w-xs truncate text-sm text-slate-600">
                                                            {
                                                                user.email
                                                            }
                                                        </p>
                                                    </td>

                                                    {isParentGuardian ? (
                                                        <>
                                                            {/* Parent Code */}
                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-600">
                                                                    {user
                                                                        .parent_guardian
                                                                        ?.parent_code ??
                                                                        'Unknown'}
                                                                </p>
                                                            </td>

                                                            {/* Occupation */}
                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-600">
                                                                    {user
                                                                        .parent_guardian
                                                                        ?.occupation ??
                                                                        'Unknown'}
                                                                </p>
                                                            </td>
                                                        </>
                                                    ) : (
                                                        <>
                                                            {/* Staff Number */}
                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-600">
                                                                    {user
                                                                        .staff
                                                                        ?.staff_number ??
                                                                        'Unknown'}
                                                                </p>
                                                            </td>

                                                            {/* Position */}
                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-600">
                                                                    {getLatestPosition(
                                                                        user.staff,
                                                                    )}
                                                                </p>
                                                            </td>

                                                            {/* Department */}
                                                            <td className="px-5 py-4">
                                                                <p className="text-sm text-slate-600">
                                                                    {user?.staff?.latest_position?.department ?? 'Unknown'}
                                                                </p>
                                                            </td>
                                                        </>
                                                    )}

                                                    {/* Action */}
                                                    <td className="px-5 py-4 text-right">
                                                        <Link
                                                            href={route(
                                                                'web.users.show',
                                                                user.id,
                                                            )}
                                                            className="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200 hover:text-slate-900"
                                                        >
                                                            View
                                                        </Link>
                                                    </td>
                                                </tr>
                                            ),
                                        )
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={
                                                    isParentGuardian
                                                        ? 5
                                                        : 6
                                                }
                                                className="px-5 py-12 text-center"
                                            >
                                                <UserRound className="mx-auto mb-3 h-8 w-8 text-slate-300" />

                                                <p className="text-sm font-medium text-slate-700">
                                                    No users found
                                                </p>

                                                <p className="mt-1 text-xs text-slate-500">
                                                    Try changing your
                                                    search or user
                                                    type filter.
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Cards */}
                        <div className="divide-y divide-slate-100 md:hidden">
                            {users.data.length > 0 ? (
                                users.data.map(
                                    (user) => (
                                        <Link
                                            key={user.id}
                                            href={route(
                                                'web.users.show',
                                                user.id,
                                            )}
                                            className="block p-4 transition hover:bg-slate-50 active:bg-slate-100"
                                        >
                                            {/* Name + Type */}
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold text-slate-800">
                                                        {
                                                            user.last_name
                                                        }
                                                        ,{' '}
                                                        {
                                                            user.first_name
                                                        }
                                                    </p>

                                                    <p className="mt-1 truncate text-xs text-slate-500">
                                                        {
                                                            user.email
                                                        }
                                                    </p>
                                                </div>

                                                <span className="shrink-0 rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                                    {isParentGuardian
                                                        ? 'Parent / Guardian'
                                                        : getLatestPosition(
                                                            user.staff,
                                                        )}
                                                </span>
                                            </div>

                                            {/* Additional Information */}
                                            <div className="mt-3 grid grid-cols-1 gap-2 text-xs sm:grid-cols-2">
                                                {isParentGuardian ? (
                                                    <>
                                                        <div className="rounded-lg bg-slate-50 px-3 py-2">
                                                            <p className="text-slate-400">
                                                                Parent Code
                                                            </p>

                                                            <p className="mt-0.5 font-medium text-slate-600">
                                                                {user
                                                                    .parent_guardian
                                                                    ?.parent_code ??
                                                                    'Unknown'}
                                                            </p>
                                                        </div>

                                                        <div className="rounded-lg bg-slate-50 px-3 py-2">
                                                            <p className="text-slate-400">
                                                                Occupation
                                                            </p>

                                                            <p className="mt-0.5 font-medium text-slate-600">
                                                                {user
                                                                    .parent_guardian
                                                                    ?.occupation ??
                                                                    'Unknown'}
                                                            </p>
                                                        </div>
                                                    </>
                                                ) : (
                                                    <>
                                                        <div className="rounded-lg bg-slate-50 px-3 py-2">
                                                            <p className="text-slate-400">
                                                                Staff No.
                                                            </p>

                                                            <p className="mt-0.5 font-medium text-slate-600">
                                                                {user
                                                                    .staff
                                                                    ?.staff_number ??
                                                                    'Unknown'}
                                                            </p>
                                                        </div>

                                                        <div className="rounded-lg bg-slate-50 px-3 py-2">
                                                            <p className="text-slate-400">
                                                                Department
                                                            </p>

                                                            <p className="mt-0.5 font-medium text-slate-600">
                                                                {user?.staff?.latest_position?.department ?? 'Unknown'}
                                                            </p>
                                                        </div>
                                                    </>
                                                )}
                                            </div>

                                            {/* Action */}
                                            <div className="mt-3 flex justify-end">
                                                <span className="text-xs font-medium text-blue-600">
                                                    View →
                                                </span>
                                            </div>
                                        </Link>
                                    ),
                                )
                            ) : (
                                <div className="px-5 py-12 text-center">
                                    <UserRound className="mx-auto mb-3 h-8 w-8 text-slate-300" />

                                    <p className="text-sm font-medium text-slate-700">
                                        No users found
                                    </p>

                                    <p className="mt-1 text-xs text-slate-500">
                                        Try changing your search or
                                        user type filter.
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Pagination */}
                        {users.links.length > 3 && (
                            <div className="overflow-x-auto border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                                <Pagination
                                    links={users.links}
                                    meta={{
                                        from: users.from,
                                        to: users.to,
                                        total: users.total,
                                    }}
                                />
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
