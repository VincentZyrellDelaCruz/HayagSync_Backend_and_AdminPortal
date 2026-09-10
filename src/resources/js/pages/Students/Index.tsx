import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { GradeSection, Paginated, Student, type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, SlidersHorizontal, UserRound, X } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

declare function route(
    name: string,
    params?: Record<string, unknown> | number | string,
): string;

interface StudentsIndexProps {
    students: Paginated<Student>;
    sections: GradeSection[];
    grades: string[];
    search: string;
    grade: string;
    section: string;
    isParent: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Students',
        href: '/students',
    },
];

const ALUMNI_VALUE = 'Alumni';

export default function StudentsIndex({ students, sections, grades, search, grade, section, isParent }: StudentsIndexProps) {
    const [searchValue, setSearchValue] = useState(search ?? '');
    const [gradeValue, setGradeValue] = useState(grade ?? '');
    const [sectionValue, setSectionValue] = useState(section ?? '');
    const [isSearching, setIsSearching] = useState(false);

    const initialized = useRef(false);

    const availableSections = useMemo(() => {
        if (!gradeValue || gradeValue === ALUMNI_VALUE) {
            return sections;
        }

        return sections.filter(
            (item) => item.grade_level === gradeValue,
        );
    }, [sections, gradeValue]);
    const applyFilters = (
        nextSearch: string,
        nextGrade: string,
        nextSection: string,
    ) => {
        const params: Record<string, string> = {};

        if (nextSearch.trim() !== '') {
            params.search = nextSearch.trim();
        }

        if (nextGrade !== '') {
            params.grade = nextGrade;
        }

        if (
            nextSection !== '' &&
            nextGrade !== ALUMNI_VALUE
        ) {
            params.section = nextSection;
        }

        setIsSearching(true);

        router.get(
            route('web.students.index'),
            params,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onFinish: () => setIsSearching(false),
            },
        );
    };

    useEffect(() => {
        if (!initialized.current) {
            initialized.current = true;
            return;
        }

        const timeout = window.setTimeout(() => {
            applyFilters(
                searchValue,
                gradeValue,
                sectionValue,
            );
        }, 400);

        return () => window.clearTimeout(timeout);
    }, [searchValue]);

    const handleGradeChange = (value: string) => {
        setGradeValue(value);
        setSectionValue('');

        applyFilters(
            searchValue,
            value,
            '',
        );
    };

    const handleSectionChange = (value: string) => {
        setSectionValue(value);

        applyFilters(
            searchValue,
            gradeValue,
            value,
        );
    };

    /*
    |--------------------------------------------------------------------------
    | Clear filters
    |--------------------------------------------------------------------------
    */
    const clearFilters = () => {
        setSearchValue('');
        setGradeValue('');
        setSectionValue('');

        applyFilters('', '', '');
    };

    const hasFilters =
        searchValue.trim() !== '' ||
        gradeValue !== '' ||
        sectionValue !== '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isParent ? "Related Students" : "Students"} />

            <div className="min-h-full bg-slate-50/60">
                <div className="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    {/* Header */}
                    <div className="mb-5 sm:mb-6">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-600">
                                <UserRound className="h-5 w-5" />
                            </div>

                            <div className="min-w-0">
                                <h1 className="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">
                                    {isParent ? 'Related Students' : 'Students List'}
                                </h1>

                                <p className="text-sm text-slate-500">
                                    {
                                        isParent
                                            ? 'Students linked to you'
                                            : 'Search student records from NEU Integrated School'
                                    }
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
                            <div className="relative md:col-span-6">
                                <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />

                                <input
                                    type="text"
                                    value={searchValue}
                                    onChange={(e) =>
                                        setSearchValue(e.target.value)
                                    }
                                    placeholder={isParent ? "Search related students by name or student number" : "Search by name or student number"}
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white pl-9 pr-10 text-sm text-slate-800 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />

                                {isSearching && (
                                    <div className="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2">
                                        <div className="h-4 w-4 animate-spin rounded-full border-2 border-slate-200 border-t-blue-600" />
                                    </div>
                                )}
                            </div>

                            {/* Grade */}
                            <div className="md:col-span-3">
                                <select
                                    name="grade"
                                    value={gradeValue}
                                    onChange={(e) =>
                                        handleGradeChange(
                                            e.target.value,
                                        )
                                    }
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    <option value="">
                                        All Grades
                                    </option>

                                    {grades.map((g) => (
                                        <option
                                            key={g}
                                            value={g}
                                        >
                                            {g}
                                        </option>
                                    ))}

                                    <option value={ALUMNI_VALUE}>
                                        Alumni
                                    </option>
                                </select>
                            </div>

                            {/* Section */}
                            <div className="md:col-span-3">
                                <select
                                    name="section"
                                    value={sectionValue}
                                    onChange={(e) =>
                                        handleSectionChange(
                                            e.target.value,
                                        )
                                    }
                                    disabled={
                                        gradeValue ===
                                        ALUMNI_VALUE
                                    }
                                    className="h-11 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-700 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-400"
                                >
                                    <option value="">
                                        {gradeValue ===
                                            ALUMNI_VALUE
                                            ? 'Not applicable'
                                            : 'All Sections'}
                                    </option>

                                    {availableSections.map(
                                        (item) => (
                                            <option
                                                key={`${item.grade_level}-${item.section}`}
                                                value={item.section}
                                            >
                                                {gradeValue
                                                    ? item.section
                                                    : `${item.grade_level} - ${item.section}`}
                                            </option>
                                        ),
                                    )}
                                </select>
                            </div>
                        </div>

                        {/* Active Filters */}
                        {hasFilters && (
                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                {searchValue && (
                                    <span className="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        Search: {searchValue}
                                    </span>
                                )}

                                {gradeValue && (
                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                        {gradeValue}
                                    </span>
                                )}

                                {sectionValue &&
                                    gradeValue !==
                                    ALUMNI_VALUE && (
                                        <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            Section:{' '}
                                            {sectionValue}
                                        </span>
                                    )}
                            </div>
                        )}
                    </div>

                    {/* Student Results */}
                    <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                        {/* Result Header */}
                        <div className="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                            <div>
                                <h2 className="text-sm font-semibold text-slate-800">
                                    {isParent
                                        ? 'Related Students'
                                        : gradeValue === ALUMNI_VALUE
                                            ? 'Alumni Students'
                                            : 'Student List'
                                    }
                                </h2>

                                <p className="text-xs text-slate-500">
                                    {students.total} {students.total === 1 ? 'student' : 'students'} found
                                </p>
                            </div>

                            {students.from !== null &&
                                students.to !== null && (
                                    <p className="text-xs text-slate-500">
                                        Showing{' '}
                                        {students.from}–
                                        {students.to} of{' '}
                                        {students.total}
                                    </p>
                                )}
                        </div>

                        {/* Desktop Table */}
                        <div className="hidden overflow-x-auto md:block">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Student No
                                        </th>

                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Name
                                        </th>

                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Grade
                                        </th>

                                        <th className="whitespace-nowrap px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                            Section
                                        </th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {students.data.length > 0 ? (
                                        students.data.map(
                                            (student) => (
                                                <tr
                                                    key={student.id}
                                                    className="cursor-pointer transition hover:bg-slate-50"
                                                >
                                                    <td className="whitespace-nowrap px-5 py-4">
                                                        <Link
                                                            href={route(
                                                                'web.students.show',
                                                                student.id,
                                                            )}
                                                            className="text-sm font-medium text-blue-600 hover:text-blue-700 hover:underline"
                                                        >
                                                            {
                                                                student.student_number
                                                            }
                                                        </Link>
                                                    </td>

                                                    <td className="px-5 py-4">
                                                        <Link
                                                            href={route(
                                                                'web.students.show',
                                                                student.id,
                                                            )}
                                                            className="text-sm font-medium text-slate-800 hover:text-blue-600"
                                                        >
                                                            {
                                                                student.last_name
                                                            }
                                                            ,{' '}
                                                            {
                                                                student.first_name
                                                            }
                                                        </Link>
                                                    </td>

                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {student
                                                            .latest_section
                                                            ?.grade_level ??
                                                            (student.isAlumni ||
                                                                gradeValue ===
                                                                ALUMNI_VALUE
                                                                ? 'Alumni'
                                                                : '-')}
                                                    </td>

                                                    <td className="whitespace-nowrap px-5 py-4 text-sm text-slate-600">
                                                        {student
                                                            .latest_section
                                                            ?.section ??
                                                            (student.isAlumni ||
                                                                gradeValue ===
                                                                ALUMNI_VALUE
                                                                ? '—'
                                                                : '-')}
                                                    </td>
                                                </tr>
                                            ),
                                        )
                                    ) : (
                                        <tr>
                                            <td
                                                colSpan={4}
                                                className="px-5 py-12 text-center"
                                            >
                                                <UserRound className="mx-auto mb-3 h-8 w-8 text-slate-300" />

                                                <p className="text-sm font-medium text-slate-700">
                                                    No students
                                                    found
                                                </p>

                                                <p className="mt-1 text-xs text-slate-500">
                                                    Try changing
                                                    your search or
                                                    filter criteria.
                                                </p>
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Cards */}
                        <div className="divide-y divide-slate-100 md:hidden">
                            {students.data.length > 0 ? (
                                students.data.map(
                                    (student) => (
                                        <Link
                                            key={student.id}
                                            href={route(
                                                'web.students.show',
                                                student.id,
                                            )}
                                            className="block p-4 transition hover:bg-slate-50 active:bg-slate-100"
                                        >
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold text-slate-800">
                                                        {
                                                            student.last_name
                                                        }
                                                        ,{' '}
                                                        {
                                                            student.first_name
                                                        }
                                                    </p>

                                                    <p className="mt-1 text-xs font-medium text-blue-600">
                                                        {
                                                            student.student_number
                                                        }
                                                    </p>
                                                </div>

                                                <span className="shrink-0 rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                                    {student
                                                        .latest_section
                                                        ?.grade_level ??
                                                        (student.isAlumni ||
                                                            gradeValue ===
                                                            ALUMNI_VALUE
                                                            ? 'Alumni'
                                                            : '—')}
                                                </span>
                                            </div>

                                            <div className="mt-3">
                                                <span className="inline-flex rounded-lg bg-slate-50 px-2.5 py-1.5 text-xs text-slate-600">
                                                    Section:{' '}
                                                    {student
                                                        .latest_section
                                                        ?.section ??
                                                        (student.isAlumni ||
                                                            gradeValue ===
                                                            ALUMNI_VALUE
                                                            ? 'Not enrolled'
                                                            : '—')}
                                                </span>
                                            </div>
                                        </Link>
                                    ),
                                )
                            ) : (
                                <div className="px-5 py-12 text-center">
                                    <UserRound className="mx-auto mb-3 h-8 w-8 text-slate-300" />

                                    <p className="text-sm font-medium text-slate-700">
                                        No students found
                                    </p>

                                    <p className="mt-1 text-xs text-slate-500">
                                        Try changing your search or
                                        filter criteria.
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Pagination */}
                        {students.links.length > 3 && (
                            <div className="overflow-x-auto border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                                <Pagination
                                    links={students.links}
                                    meta={{
                                        from: students.from,
                                        to: students.to,
                                        total: students.total,
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
