import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { GradeSection, Position, Student, User, type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Briefcase, Calendar, ChevronRight, Clock, GraduationCap, Mail, Phone, ShieldCheck, Users as UsersIcon,
} from 'lucide-react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface UserInfoProps {
    user: User;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
    { title: 'User Information', href: '#' },
];

const formatDate = (date: string | null | undefined) => {
    if (!date) return 'N/A';
    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) return date;
    return parsed.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
};

const formatDateTime = (date: string | null | undefined) => {
    if (!date) return 'N/A';
    const parsed = new Date(date);
    if (Number.isNaN(parsed.getTime())) return date;
    return parsed.toLocaleDateString('en-US', {
        month: 'short',
        day: '2-digit',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    });
};

function InfoField({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">{label}</p>
            <p className="text-sm text-slate-800">{value}</p>
        </div>
    );
}

function SectionCard({ title, children }: { title: string; children: React.ReactNode }) {
    return (
        <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 className="text-sm font-semibold text-slate-700">{title}</h2>
            </div>
            {children}
        </div>
    );
}

export default function UserInfo({ user }: UserInfoProps) {
    const getInitials = useInitials();

    const sortedPositions = [...(user.staff?.positions ?? [])].sort((a, b) => {
        const dateA = a.pivot?.assigned_at ? new Date(a.pivot.assigned_at).getTime() : 0;
        const dateB = b.pivot?.assigned_at ? new Date(b.pivot.assigned_at).getTime() : 0;
        return dateB - dateA;
    });

    const currentPosition: Position | null = user.staff?.latest_position ?? sortedPositions[0] ?? null;
    const isStaff = !!user.staff;
    const isParentGuardian = !!user.parent_guardian;
    const linkedStudents = user.parent_guardian?.students ?? [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${user.last_name}, ${user.first_name}`} />

            <div className="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
                {/* <div>
                    <Link
                        href={route('web.users.index')}
                        className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-slate-900"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        Back to User Directory
                    </Link>
                </div> */}

                {/* Header */}
                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-6 px-6 py-6 lg:flex-row lg:items-start lg:justify-between">
                        {/* Left: avatar + identity */}
                        <div className="flex flex-col items-center gap-4 text-center lg:flex-row lg:items-center lg:text-left">
                            <Avatar className="h-16 w-16 shrink-0 overflow-hidden rounded-full ring-2 ring-slate-100">
                                <AvatarImage src={user.avatar} alt={user.name} />
                                <AvatarFallback className="rounded-full bg-slate-900 text-base font-semibold text-white">
                                    {getInitials(user.name ?? 'Unknown')}
                                </AvatarFallback>
                            </Avatar>

                            <div>
                                <div className="flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                                    <h1 className="text-2xl font-bold text-slate-800">
                                        {user.last_name}, {user.first_name}
                                    </h1>
                                </div>

                                <div className="mt-2 flex flex-wrap items-center justify-center gap-2 lg:justify-start">
                                    {isStaff && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium text-blue-700">
                                            <Briefcase className="h-3 w-3" />
                                            Staff
                                        </span>
                                    )}
                                    {isParentGuardian && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                            <UsersIcon className="h-3 w-3" />
                                            Parent/Guardian
                                        </span>
                                    )}
                                    {isStaff && user.staff?.is_admin && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-1 text-xs font-semibold text-purple-700">
                                            <ShieldCheck className="h-3 w-3" />
                                            Admin
                                        </span>
                                    )}
                                    {currentPosition && (
                                        <span className="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700">
                                            {currentPosition.position_name}
                                        </span>
                                    )}
                                </div>

                                <div className="mt-3 flex flex-col items-center gap-1.5 text-sm text-slate-500 lg:flex-row lg:items-center lg:gap-4">
                                    <span className="inline-flex items-center gap-1.5">
                                        <Mail className="h-3.5 w-3.5 text-slate-400" />
                                        {user.email}
                                    </span>
                                    <span className="inline-flex items-center gap-1.5">
                                        <Phone className="h-3.5 w-3.5 text-slate-400" />
                                        {user.phone_number ?? 'No phone'}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Right: metadata */}
                        <div className="shrink-0 text-sm text-slate-500 lg:text-right">
                            <p className="inline-flex items-center gap-1.5 font-medium text-slate-700 lg:justify-end">
                                <Calendar className="h-3.5 w-3.5 text-slate-400" />
                                Registered {formatDateTime(user.created_at)}
                            </p>
                            <p className="mt-1 inline-flex items-center gap-1.5 lg:justify-end">
                                <Clock className="h-3.5 w-3.5 text-slate-400" />
                                Last accessed {formatDateTime(user.updated_at)}
                            </p>
                        </div>
                    </div>
                </div>

                {/* Quick Stats */}
                {(isStaff || isParentGuardian) && (
                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-3">
                        {isStaff && (
                            <>
                                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                            <Briefcase className="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p className="text-lg font-bold text-slate-800">{sortedPositions.length}</p>
                                            <p className="text-xs text-slate-500">Position{sortedPositions.length !== 1 ? 's' : ''} Held</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div className="flex items-center gap-3">
                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                            <GraduationCap className="h-4 w-4" />
                                        </span>
                                        <div>
                                            <p className="text-lg font-bold text-slate-800">{user.staff?.section_advisers?.length ?? 0}</p>
                                            <p className="text-xs text-slate-500">Section{(user.staff?.section_advisers?.length ?? 0) !== 1 ? 's' : ''} Advised</p>
                                        </div>
                                    </div>
                                </div>
                            </>
                        )}
                        {isParentGuardian && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                        <UsersIcon className="h-4 w-4" />
                                    </span>
                                    <div>
                                        <p className="text-lg font-bold text-slate-800">{linkedStudents.length}</p>
                                        <p className="text-xs text-slate-500">Linked Student{linkedStudents.length !== 1 ? 's' : ''}</p>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                )}

                {/* Main Grid */}
                <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                    {/* Main Content */}
                    <section className="space-y-6 lg:col-span-8">
                        {/* Staff Information */}
                        {isStaff && (
                            <>
                                <SectionCard title="Staff Information">
                                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                                        <InfoField label="Staff No" value={user.staff?.staff_number ?? 'N/A'} />
                                        <InfoField label="Gender" value={user.gender ?? 'N/A'} />
                                        <InfoField label="Birthdate" value={formatDate(user.birthdate)} />
                                        <InfoField label="Department" value={currentPosition?.department ?? 'N/A'} />
                                        <InfoField
                                            label="Admin Access"
                                            value={
                                                user.staff?.is_admin ? (
                                                    <span className="inline-flex items-center gap-1 rounded bg-purple-100 px-2 py-1 text-xs font-semibold text-purple-700">
                                                        <ShieldCheck className="h-3 w-3" />
                                                        Yes
                                                    </span>
                                                ) : (
                                                    'No'
                                                )
                                            }
                                        />
                                    </div>
                                </SectionCard>

                                {/* Position Timeline */}
                                <SectionCard title="Position Timeline">
                                    <div className="p-6">
                                        {sortedPositions.length > 0 ? (
                                            sortedPositions.map((position, index) => {
                                                const isLast = index === sortedPositions.length - 1;
                                                return (
                                                    <div key={position.id} className={`relative pl-8 ${!isLast ? 'pb-6' : ''}`}>
                                                        {!isLast && (
                                                            <div className="absolute left-1.75 top-5 bottom-0 w-0.5 bg-slate-200" />
                                                        )}
                                                        <div
                                                            className={`absolute left-0 top-1 h-4 w-4 rounded-full border-4 border-white shadow-sm ${
                                                                index === 0 ? 'bg-blue-600' : 'bg-slate-300'
                                                            }`}
                                                        />
                                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                                            <div className="flex items-center gap-2">
                                                                <span className="text-sm font-medium text-slate-800">
                                                                    {position.position_name}
                                                                </span>
                                                                {index === 0 && (
                                                                    <span className="rounded-full bg-blue-100 px-2 py-0.5 text-[11px] font-semibold text-blue-700">
                                                                        Current
                                                                    </span>
                                                                )}
                                                            </div>
                                                            <span className="text-xs text-slate-500">
                                                                {position.pivot?.assigned_at ? formatDate(position.pivot.assigned_at) : 'N/A'}
                                                            </span>
                                                        </div>
                                                        {position.department && (
                                                            <p className="mt-0.5 text-xs text-slate-500">{position.department}</p>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        ) : (
                                            <p className="text-sm text-slate-500">No positions assigned.</p>
                                        )}
                                    </div>
                                </SectionCard>

                                {/* Adviser Sections */}
                                {user.staff?.section_advisers && user.staff.section_advisers.length > 0 && (
                                    <SectionCard title="Adviser Sections">
                                        <div className="divide-y divide-slate-100">
                                            {user.staff.section_advisers.map((section: GradeSection) => (
                                                <div key={section.id} className="flex items-center justify-between gap-4 px-6 py-4">
                                                    <div className="flex items-center gap-3">
                                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                                            <GraduationCap className="h-4 w-4" />
                                                        </span>
                                                        <span className="text-sm font-medium text-slate-800">
                                                            {section.grade_level} - {section.section}
                                                        </span>
                                                    </div>
                                                    <span className="shrink-0 text-xs text-slate-500">
                                                        {section.school_year?.school_year ?? 'N/A'}
                                                    </span>
                                                </div>
                                            ))}
                                        </div>
                                    </SectionCard>
                                )}
                            </>
                        )}

                        {/* Parent / Guardian Information */}
                        {isParentGuardian && (
                            <>
                                <SectionCard title="Parent/Guardian Information">
                                    <div className="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">
                                        <InfoField label="Parent Code" value={user.parent_guardian?.parent_code ?? 'N/A'} />
                                        <InfoField label="Occupation" value={user.parent_guardian?.occupation ?? 'N/A'} />
                                        <InfoField label="Gender" value={user.gender ?? 'N/A'} />
                                        <InfoField label="Birthdate" value={formatDate(user.birthdate)} />
                                    </div>
                                </SectionCard>

                                {/* Linked Students */}
                                <SectionCard title="Linked Students">
                                    <div className="divide-y divide-slate-100">
                                        {linkedStudents.length > 0 ? (
                                            linkedStudents.map((student: Student) => (
                                                <Link
                                                    key={student.id}
                                                    href={route('web.students.show', student.id)}
                                                    className="flex items-center justify-between gap-4 px-6 py-4 transition hover:bg-slate-50"
                                                >
                                                    <div className="flex min-w-0 items-center gap-3">
                                                        <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-xs font-semibold text-emerald-700">
                                                            {getInitials(`${student.first_name} ${student.last_name}`)}
                                                        </span>
                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-medium text-slate-800">
                                                                {student.last_name}, {student.first_name}
                                                            </p>
                                                            <p className="text-xs text-slate-500">
                                                                {student.pivot?.relationship ?? 'N/A'}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <ChevronRight className="h-4 w-4 shrink-0 text-slate-400" />
                                                </Link>
                                            ))
                                        ) : (
                                            <p className="px-6 py-6 text-sm text-slate-500">No linked students.</p>
                                        )}
                                    </div>
                                </SectionCard>
                            </>
                        )}

                        {/* No Role Information */}
                        {!isStaff && !isParentGuardian && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-6 text-center text-sm text-slate-500 shadow-sm">
                                No additional staff or parent/guardian information is available for this user.
                            </div>
                        )}
                    </section>

                    {/* Right Sidebar */}
                    <aside className="space-y-6 self-start lg:sticky lg:top-6 lg:col-span-4">
                        <SectionCard title="Record Metadata">
                            <div className="space-y-4 p-6 text-sm">
                                <InfoField label="User ID" value={<span className="break-all">{user.id}</span>} />
                                <InfoField label="Registered At" value={formatDateTime(user.created_at)} />
                                <InfoField label="Last Updated" value={formatDateTime(user.updated_at)} />
                                <InfoField
                                    label="Email Verified"
                                    value={
                                        user.email_verified_at ? (
                                            <span className="inline-flex items-center gap-1 rounded bg-green-100 px-2 py-1 text-xs font-semibold text-green-700">
                                                <ShieldCheck className="h-3 w-3" />
                                                Verified
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">
                                                Not verified
                                            </span>
                                        )
                                    }
                                />
                            </div>
                        </SectionCard>
                    </aside>
                </div>
            </div>
        </AppLayout>
    );
}
