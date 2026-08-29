import { EmptyState } from '@/components/ui/empty-state';
import { InfoField } from '@/components/ui/info-field';
import { SectionCard } from '@/components/ui/section-card';
import AppLayout from '@/layouts/app-layout';
import { Student, User, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight, ClipboardList, Database, FileText, ShieldAlert, Users } from 'lucide-react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface StudentInfoProps {
    student: Student;
}

const formatDate = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatDateTime = (value: string | null) => {
    if (!value) return 'N/A';
    return new Date(value).toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const fullName = (user: User | null) => {
    if (!user) return 'Unknown';
    return `${user.last_name}, ${user.first_name}`;
};

const statusClasses = (status?: string) => {
    switch (status) {
        case 'Active':
            return 'bg-green-100 text-green-700';
        case 'Inactive':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

export default function StudentInfo({ student }: StudentInfoProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Students', href: route('web.students.index') },
        { title: `${student.last_name}, ${student.first_name}`, href: route('web.students.show', student.id) },
    ];

    const gradeSection = student.latest_enrollment?.grade_section;
    const parentGuardians = student.parent_guardians ?? [];
    const reports = student.reports ?? [];
    const disciplinaryActions = student.disciplinary_actions ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${student.last_name}, ${student.first_name}`} />

            <div className="space-y-6 m-10">
                {/* Header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="px-6 py-6">
                        <h1 className="text-2xl font-bold text-slate-800">
                            {student.last_name}, {student.first_name}
                        </h1>

                        <p className="mt-1 text-sm text-slate-500">Student No: {student.student_number}</p>

                        <div className="mt-3 flex flex-wrap items-center gap-2">
                            {student.status && (
                                <span className={`inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ${statusClasses(student.status)}`}>
                                    {student.status}
                                </span>
                            )}
                            {gradeSection && (
                                <span className="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                    {gradeSection.grade_level} - {gradeSection.section}
                                </span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Quick Stats */}
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <Users className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{parentGuardians.length}</p>
                                <p className="text-xs text-slate-500">Parent/Guardian{parentGuardians.length !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <FileText className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{reports.length}</p>
                                <p className="text-xs text-slate-500">Report{reports.length !== 1 ? 's' : ''} Involved</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                                <ShieldAlert className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{disciplinaryActions.length}</p>
                                <p className="text-xs text-slate-500">Disciplinary Action{disciplinaryActions.length !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Main Content */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    {/* Left Column */}
                    <section className="space-y-6 lg:col-span-8">
                        {/* Basic Information */}
                        <SectionCard title="Basic Information" icon={<ClipboardList className="w-4 h-4 text-slate-400" />}>
                            <div className="grid grid-cols-1 gap-5 p-6 sm:grid-cols-2">
                                <InfoField label="Gender" value={student.gender ?? 'N/A'} />
                                <InfoField label="Birthdate" value={formatDate(student.birthdate)} />
                                <InfoField label="Email" value={<span className="break-all">{student.email ?? 'N/A'}</span>} />
                                <InfoField label="Phone" value={student.phone_number ?? 'N/A'} />
                                <InfoField label="Grade" value={gradeSection?.grade_level ?? 'N/A'} />
                                <InfoField label="Section" value={gradeSection?.section ?? 'N/A'} />
                                <InfoField label="School Year" value={gradeSection?.school_year?.school_year ?? 'N/A'} />
                                <InfoField label="Status" value={student.status ?? 'N/A'} />
                            </div>
                        </SectionCard>

                        {/* Parent / Guardian Relationships */}
                        <SectionCard
                            title="Parent/Guardian Relationships"
                            icon={<Users className="w-4 h-4 text-slate-400" />}
                            right={<span className="text-xs text-slate-500">{parentGuardians.length} total</span>}
                        >
                            <div className="divide-y divide-slate-100">
                                {parentGuardians.length > 0 ? (
                                    parentGuardians.map((parentGuardian) => (
                                        <Link
                                            key={parentGuardian.id}
                                            href={route('web.users.show', parentGuardian.user.id)}
                                            className="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50"
                                        >
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-slate-800">{fullName(parentGuardian.user)}</p>
                                                <p className="mt-0.5 text-xs text-slate-500">
                                                    {parentGuardian.pivot.relationship ?? 'Not specified'}
                                                </p>
                                            </div>
                                            <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                                        </Link>
                                    ))
                                ) : (
                                    <EmptyState icon={<Users className="w-6 h-6" />} title="No parent/guardian linked" />
                                )}
                            </div>
                        </SectionCard>

                        {/* Reports Involved */}
                        <SectionCard
                            title="Reports Involved"
                            icon={<FileText className="w-4 h-4 text-slate-400" />}
                            right={<span className="text-xs text-slate-500">{reports.length} total</span>}
                        >
                            <div className="divide-y divide-slate-100">
                                {reports.length > 0 ? (
                                    reports.map((report) => (
                                        <Link
                                            key={report.id}
                                            href={route('web.reports.show', report.id)}
                                            className="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <p className="truncate text-sm font-medium text-slate-800">
                                                    {report.incident_title ?? 'Incident'}
                                                </p>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    Category: {report.category?.category_name ?? 'N/A'}
                                                    <span className="mx-1">•</span>
                                                    Involvement: {report.pivot.involvement_type ?? 'N/A'}
                                                </p>
                                                {report.pivot.notes && (
                                                    <p className="mt-1 truncate text-xs text-slate-400">{report.pivot.notes}</p>
                                                )}
                                            </div>
                                            <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                                        </Link>
                                    ))
                                ) : (
                                    <EmptyState icon={<FileText className="w-6 h-6" />} title="No reports linked" />
                                )}
                            </div>
                        </SectionCard>

                        {/* Disciplinary Actions */}
                        <SectionCard
                            title="Disciplinary Actions"
                            icon={<ShieldAlert className="w-4 h-4 text-slate-400" />}
                            right={<span className="text-xs text-slate-500">{disciplinaryActions.length} total</span>}
                        >
                            <div className="p-6">
                                {disciplinaryActions.length > 0 ? (
                                    <div className="space-y-3 text-sm">
                                        {disciplinaryActions.map((action) => (
                                            <div key={action.id} className="rounded-lg border border-slate-200 p-4">
                                                <p className="font-medium text-slate-800">{action.discipline_action}</p>
                                                <p className="mt-1 text-xs text-slate-600">
                                                    <span className="font-medium">Imposed By:</span>{' '}
                                                    {action.staff?.user ? fullName(action.staff.user) : 'Unknown Staff'}
                                                </p>
                                                {action.notes && (
                                                    <div className="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                                        <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                            Notes
                                                        </p>
                                                        <p className="whitespace-pre-line text-xs text-slate-600">{action.notes}</p>
                                                    </div>
                                                )}
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <EmptyState
                                        icon={<ShieldAlert className="w-6 h-6" />}
                                        title="No disciplinary actions recorded"
                                        className="px-0 py-6"
                                    />
                                )}
                            </div>
                        </SectionCard>
                    </section>

                    {/* Right Sidebar */}
                    <aside className="space-y-6 self-start lg:sticky lg:top-6 lg:col-span-4">
                        <SectionCard title="Record Metadata" icon={<Database className="w-4 h-4 text-slate-400" />}>
                            <div className="space-y-4 p-6">
                                <InfoField label="Student ID" value={<span className="break-all">{student.id}</span>} />
                                <InfoField label="Registered At" value={formatDateTime(student.created_at)} />
                                <InfoField label="Last Updated" value={formatDateTime(student.updated_at)} />
                            </div>
                        </SectionCard>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
