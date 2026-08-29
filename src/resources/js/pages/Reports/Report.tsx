import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/ui/empty-state';
import { InfoField } from '@/components/ui/info-field';
import Modal from '@/components/ui/modal';
import { SectionCard } from '@/components/ui/section-card';
import AppLayout from '@/layouts/app-layout';
import {
    type Auth,
    type BreadcrumbItem,
    type ReportCategory,
    type ReportPivot,
    type ReportStatus,
    type ReportUser,
    Report as SharedReport,
    type Student,
} from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { CalendarClock, CalendarSync, CircleCheckBig, ClipboardList, Clock, Database, FileText, History, Image as ImageIcon, ImageOff, Send, Users, X, } from 'lucide-react';
import { FormEvent, useState } from 'react';

declare function route(
    name: string,
    params?: Record<string, unknown> | number | string | (number | string)[],
): string;

interface ReportUpdateStaff {
    staff_number?: string | null;
    department?: string | null;
}

interface ReportUpdateUser extends ReportUser {
    staff?: ReportUpdateStaff | null;
}

interface ReportUpdate {
    id: string;
    note: string | null;
    created_at: string;
    report_status: ReportStatus | null;
    user: ReportUpdateUser | null;
}

interface Evidence {
    id: string;
    file_path: string;
    file_name: string;
    mime_type: string;
}

type StudentInvolved = Pick<
    Student,
    'id' | 'first_name' | 'last_name' | 'middle_name' | 'suffix' | 'student_number' | 'gender' | 'email' | 'phone_number'
> & {
    pivot: ReportPivot;
};

interface ChatMessage {
    id: string;
    sender_id: string;
    message: string;
    created_at: string;
}

interface Meeting {
    id: string;
    meeting_date: string;
    status: 'Active' | 'Canceled' | 'Finished' | string;
    notes: string | null;
    scheduler: ReportUser | null;
    chatMessages: ChatMessage[];
}

interface ReportDetail extends Omit<SharedReport, 'current_status' | 'category'> {
    incident_date: string | null;
    updated_at: string;
    category: ReportCategory | null;
    current_status: ReportStatus;
    latest_update: ReportUpdate | null;
    report_evidences: Evidence[];
    students: StudentInvolved[];
    report_updates: ReportUpdate[];
    meetings: Meeting[];
}

interface ReportShowProps {
    report: ReportDetail;
}

interface PageProps {
    auth: Auth;
    [key: string]: unknown;
}

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

const timelineBadgeClasses = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-red-100 text-red-700';
        case 'Under Investigation':
            return 'bg-orange-100 text-orange-700';
        case 'Scheduled':
            return 'bg-blue-100 text-blue-700';
        case 'Resolved':
            return 'bg-green-100 text-green-700';
        case 'Unresolved':
            return 'bg-rose-100 text-rose-700';
        case 'Cancelled':
            return 'bg-slate-200 text-slate-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const meetingStatusClasses = (status: string) => {
    switch (status) {
        case 'Active':
            return 'bg-green-100 text-green-700';
        case 'Canceled':
            return 'bg-red-100 text-red-700';
        case 'Finished':
            return 'bg-blue-100 text-blue-700';
        default:
            return 'bg-gray-100 text-gray-700';
    }
};

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });

const formatTime = (value: string) =>
    new Date(value).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });

const formatDateTime = (value: string) => `${formatDate(value)} ${formatTime(value)}`;

const formatMinDate = (): string => {
    const today = new Date();
    const tomorrow = new Date(today);

    tomorrow.setDate(today.getDate() + 1);
    tomorrow.setHours(0, 0, 0, 0);

    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const day = String(tomorrow.getDate()).padStart(2, '0');
    const hours = String(tomorrow.getHours()).padStart(2, '0');
    const minutes = String(tomorrow.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const studentFullName = (student: StudentInvolved) =>
    [`${student.last_name},`, student.first_name, student.middle_name, student.suffix].filter(Boolean).join(' ');

export default function Report({ report }: ReportShowProps) {
    const { auth } = usePage<PageProps>().props;

    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [openChats, setOpenChats] = useState<Record<string, boolean>>({});
    const [messageDrafts, setMessageDrafts] = useState<Record<string, string>>({});
    const [preview, setPreview] = useState<{ type: 'image' | 'video'; src: string; mime: string } | null>(null);

    const latestUpdate = report.latest_update;
    const status = latestUpdate?.report_status?.status_name ?? 'No Status';
    const canAct = !['Cancelled', 'Resolved', 'Dismissed'].includes(report.current_status.status_name);
    const reporter = report.user ? `${report.user.last_name}, ${report.user.first_name}` : 'Unknown Reporter';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Incident Inbox', href: route('web.reports.index') },
        { title: report.incident_title, href: route('web.reports.show', report.id) },
    ];

    const scheduleForm = useForm({
        meeting_datetime: '',
        note: '',
        status_id: 3,
        type: 'schedule',
    });

    const submitSchedule = (e: FormEvent) => {
        e.preventDefault();

        scheduleForm.put(route('web.reports.update', report.id), {
            preserveScroll: true,
            onSuccess: () => {
                setScheduleOpen(false);
                scheduleForm.reset();
            },
        });
    };

    const toggleChat = (meetingId: string) => {
        setOpenChats((prev) => ({ ...prev, [meetingId]: !prev[meetingId] }));
    };

    /*
     * Only Active meetings can send messages.
     * This frontend check improves UX, but the backend ChatController must still enforce the rule.
     */
    const sendMessage = (meeting: Meeting) => {
        if (meeting.status !== 'Active') {
            return;
        }

        const message = messageDrafts[meeting.id]?.trim();

        if (!message) {
            return;
        }

        router.post(
            route('web.chat_messages.store', meeting.id),
            { message },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMessageDrafts((prev) => ({ ...prev, [meeting.id]: '' }));
                },
            },
        );
    };

    const updateMeeting = (meetingId: string, action: 'cancel' | 'finish') => {
        router.put(route('web.meetings.update', [meetingId, action]), {}, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incident Details" />

            <div className="space-y-8 m-10">
                {/* HEADER */}
                <div className="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div className="px-6 py-6 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">

                        {/* Left side: status + title + description */}
                        <div className="min-w-0">
                            <div className="flex flex-wrap items-center gap-2 mb-3">
                                <span className={`px-3 py-1 rounded-full text-xs font-medium ${badgeClasses(status)}`}>
                                    {status}
                                </span>

                                {['Pending', 'Under Investigation'].includes(status) && (
                                <span className="px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    Needs Attention
                                </span>
                                )}
                            </div>

                            <h1 className="text-2xl font-bold text-slate-800">{report.incident_title}</h1>

                            <p className="text-sm text-slate-500 mt-2">
                                Detailed incident case overview, involved students, and review timeline.
                            </p>
                        </div>

                        {/* Right side: metadata + actions */}
                        <div className="flex flex-col items-start lg:items-end gap-3 shrink-0 text-sm text-slate-500">

                            {/* Metadata */}
                            <div className="flex flex-col items-start lg:items-end">
                                <p className="font-medium text-slate-700 inline-flex items-center gap-1.5">
                                    <CalendarClock className="w-3.5 h-3.5 text-slate-400" />
                                    {formatDate(report.created_at)}
                                </p>
                                <p className="inline-flex items-center gap-1.5 mt-0.5">
                                    <Clock className="w-3.5 h-3.5 text-slate-400" />
                                    {formatTime(report.created_at)}
                                </p>
                            </div>

                            {/* Action buttons */}
                            {canAct && (
                                <div className="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        onClick={() => setScheduleOpen(true)}
                                        className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 cursor-pointer flex items-center gap-1.5"
                                    >
                                        <CalendarSync />
                                        Schedule Meeting
                                    </Button>

                                    {
                                        auth.user?.staff?.latest_position?.position_name !== 'OSD Officer' && (
                                            <Button
                                                type="button"
                                                className="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700 cursor-pointer flex items-center gap-1.5"
                                            >
                                                <Send />
                                                {auth.user?.staff?.latest_position?.position_name === 'Teacher' ? 'Forward to Ministro/Principal'  : 'Forward to OSD Officer'}
                                            </Button>
                                        )
                                    }

                                    {
                                        auth.user?.staff && (
                                            <>
                                                <Button
                                                    type="button"
                                                    className="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700 cursor-pointer flex items-center gap-1.5"
                                                >
                                                    <CircleCheckBig />
                                                    Resolve
                                                </Button>
                                                <Button
                                                    type="button"
                                                    className="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 cursor-pointer flex items-center gap-1.5"
                                                >
                                                    <X />
                                                    Drop
                                                </Button>
                                            </>
                                        )
                                    }
                                </div>
                            )}
                        </div>
                    </div>
                </div>


                {/* QUICK STATS */}
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700">
                                <Users className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{report.students.length}</p>
                                <p className="text-xs text-slate-500">Student{report.students.length !== 1 ? 's' : ''} Involved</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                <ImageIcon className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{report.report_evidences.length}</p>
                                <p className="text-xs text-slate-500">Evidence File{report.report_evidences.length !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-100 text-purple-700">
                                <History className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{report.report_updates.length}</p>
                                <p className="text-xs text-slate-500">Case Update{report.report_updates.length !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                <CalendarClock className="h-4 w-4" />
                            </span>
                            <div>
                                <p className="text-lg font-bold text-slate-800">{report.meetings.length}</p>
                                <p className="text-xs text-slate-500">Meeting{report.meetings.length !== 1 ? 's' : ''}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {/* MAIN GRID */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* MAIN CONTENT */}
                    <section className="lg:col-span-8 space-y-6">
                        {/* INCIDENT OVERVIEW */}
                        <SectionCard title="Incident Overview" icon={<ClipboardList className="w-4 h-4 text-slate-400" />}>
                            <div className="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                                <InfoField label="Reported By" value={reporter} />
                                <InfoField label="Category" value={report.category?.category_name ?? 'N/A'} />
                                <InfoField
                                    label="Incident Date & Time"
                                    value={report.incident_date ? formatDate(report.incident_date) : 'Not specified'}
                                />
                                <InfoField label="Location" value={report.location ?? 'Not specified'} />
                            </div>
                        </SectionCard>

                        {/* EVIDENCES */}
                        <SectionCard title="Evidences" icon={<ImageIcon className="w-4 h-4 text-slate-400" />}>
                            <div className="p-6">
                                {report.report_evidences.length > 0 ? (
                                    <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        {report.report_evidences.map((evidence) => {
                                            const src = `/storage/${evidence.file_path}`;
                                            const isImage = evidence.mime_type.startsWith('image/');

                                            return (
                                                <div
                                                    key={evidence.id}
                                                    className="cursor-pointer rounded overflow-hidden shadow hover:opacity-80"
                                                    onClick={() =>
                                                        setPreview({ type: isImage ? 'image' : 'video', src, mime: evidence.mime_type })
                                                    }
                                                >
                                                    {isImage ? (
                                                        <img src={src} alt={evidence.file_name} className="w-full h-40 object-cover" />
                                                    ) : (
                                                        <video className="w-full h-40 object-cover" muted>
                                                            <source src={src} type={evidence.mime_type} />
                                                        </video>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <EmptyState
                                        icon={<ImageOff className="w-6 h-6" />}
                                        title="No evidence uploaded"
                                        description="No image or video evidence was provided for this incident."
                                        className="px-0 py-6"
                                    />
                                )}
                            </div>
                        </SectionCard>

                        {/* DESCRIPTION */}
                        <SectionCard title="Incident Description" icon={<FileText className="w-4 h-4 text-slate-400" />}>
                            <div className="p-6">
                                {report.description ? (
                                    <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">{report.description}</p>
                                ) : (
                                    <p className="text-sm text-slate-500">No description provided for this incident.</p>
                                )}
                            </div>
                        </SectionCard>

                        {/* STUDENTS INVOLVED */}
                        <SectionCard
                            title="Students Involved"
                            icon={<Users className="w-4 h-4 text-slate-400" />}
                            right={
                                <span className="text-xs text-slate-500">
                                    {report.students.length} student{report.students.length !== 1 ? 's' : ''}
                                </span>
                            }
                        >
                            <div className="divide-y divide-slate-200">
                                {report.students.length > 0 ? (
                                    report.students.map((student) => (
                                        <div key={student.id} className="p-6">
                                            <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2 mb-2">
                                                        <Link
                                                            href={route('web.students.show', student.id)}
                                                            className="text-base font-semibold text-slate-800 hover:text-blue-600 hover:underline transition"
                                                        >
                                                            {studentFullName(student)}
                                                        </Link>

                                                        <span className="px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                                            {student.pivot.involvement_type ?? 'Not specified'}
                                                        </span>
                                                    </div>

                                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm text-slate-600">
                                                        <div>
                                                            <span className="font-medium text-slate-700">Student No:</span>{' '}
                                                            {student.student_number ?? 'N/A'}
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-slate-700">Gender:</span>{' '}
                                                            {student.gender ?? 'N/A'}
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-slate-700">Email:</span>{' '}
                                                            {student.email ?? 'N/A'}
                                                        </div>
                                                        <div>
                                                            <span className="font-medium text-slate-700">Phone:</span>{' '}
                                                            {student.phone_number ?? 'N/A'}
                                                        </div>
                                                    </div>

                                                    {student.pivot.notes && (
                                                        <div className="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-200">
                                                            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                                                Involvement Notes
                                                            </p>
                                                            <p className="text-sm text-slate-700 whitespace-pre-line">{student.pivot.notes}</p>
                                                        </div>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <EmptyState
                                        icon={<Users className="w-6 h-6" />}
                                        title="No students linked"
                                        description="No student records are associated with this incident yet."
                                    />
                                )}
                            </div>
                        </SectionCard>

                        {/* TIMELINE / UPDATES */}
                        <SectionCard
                            title="Case Timeline"
                            icon={<History className="w-4 h-4 text-slate-400" />}
                            right={
                                <span className="text-xs text-slate-500">
                                    {report.report_updates.length} update{report.report_updates.length !== 1 ? 's' : ''}
                                </span>
                            }
                        >
                            <div className="p-6">
                                {report.report_updates.length > 0 ? (
                                    report.report_updates.map((update, index) => {
                                        const updateStatus = update.report_status?.status_name ?? 'Unknown Status';
                                        const updater = update.user;
                                        const updaterName = updater ? `${updater.first_name} ${updater.last_name}`.trim() : 'Unknown Staff';
                                        const isLast = index === report.report_updates.length - 1;

                                        return (
                                            <div key={update.id} className={`relative pl-8 ${!isLast ? 'pb-8' : ''}`}>
                                                {!isLast && <div className="absolute left-2.75 top-6 bottom-0 w-0.5 bg-slate-200" />}
                                                <div className="absolute left-0 top-1 w-6 h-6 rounded-full bg-slate-900 border-4 border-white shadow-sm" />

                                                <div className="bg-slate-50 border border-slate-200 rounded-2xl p-5">
                                                    <div className="flex flex-col md:flex-row md:items-start md:justify-between gap-3 mb-3">
                                                        <div>
                                                            <div className="flex flex-wrap items-center gap-2 mb-2">
                                                                <span
                                                                    className={`px-2.5 py-1 rounded-full text-xs font-medium ${timelineBadgeClasses(updateStatus)}`}
                                                                >
                                                                    {updateStatus}
                                                                </span>

                                                                {index === 0 && (
                                                                    <span className="px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-900 text-white">
                                                                        Latest
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <h3 className="text-sm font-semibold text-slate-800">Updated by {updaterName}</h3>

                                                            <p className="text-xs text-slate-500 mt-1">
                                                                {updater?.staff ? (
                                                                    <>
                                                                        Staff No: {updater.staff.staff_number ?? 'N/A'}
                                                                        {updater.staff.department && <> • {updater.staff.department}</>}
                                                                    </>
                                                                ) : (
                                                                    'Staff record not found'
                                                                )}
                                                            </p>
                                                        </div>

                                                        <div className="text-xs text-slate-500 md:text-right shrink-0">
                                                            <p className="font-medium text-slate-700">{formatDate(update.created_at)}</p>
                                                            <p>{formatTime(update.created_at)}</p>
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
                                                            Review Note
                                                        </p>
                                                        {update.note ? (
                                                            <p className="text-sm text-slate-700 whitespace-pre-line leading-relaxed">
                                                                {update.note}
                                                            </p>
                                                        ) : (
                                                            <p className="text-sm text-slate-500 italic">No note provided for this update.</p>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    })
                                ) : (
                                    <EmptyState
                                        icon={<History className="w-6 h-6" />}
                                        title="No incident updates yet"
                                        description="This case has not been reviewed or updated yet."
                                    />
                                )}
                            </div>
                        </SectionCard>
                    </section>

                    {/* RIGHT SIDEBAR */}
                    <aside className="lg:col-span-4 space-y-6 lg:sticky lg:top-6 self-start">
                        {/* METADATA */}
                        <SectionCard title="Record Metadata" icon={<Database className="w-4 h-4 text-slate-400" />}>
                            <div className="p-6 space-y-4">
                                <InfoField label="Incident ID" value={<span className="break-all">{report.id}</span>} />
                                <InfoField label="Created At" value={formatDateTime(report.created_at)} />
                                <InfoField label="Last Updated" value={formatDateTime(latestUpdate?.created_at ?? report.updated_at)} />
                                <InfoField label="Total Updates" value={report.report_updates.length} />
                            </div>
                        </SectionCard>

                        {/* MEETINGS */}
                        <SectionCard title="Meetings" icon={<CalendarClock className="w-4 h-4 text-slate-400" />}>
                            <div className="p-6 space-y-4 text-sm">
                                {report.meetings.length > 0 ? (
                                    report.meetings.map((meeting) => {
                                        const isChatOpen = !!openChats[meeting.id];
                                        const isActive = meeting.status === 'Active';

                                        return (
                                            <div key={meeting.id} className="border border-slate-200 rounded-xl p-4 space-y-3">
                                                {/* Meeting Header */}
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="font-medium text-slate-800">{formatDateTime(meeting.meeting_date)}</p>
                                                        <p className="text-xs text-slate-500 mt-1">
                                                            Scheduled By:{' '}
                                                            <span className="text-slate-700">
                                                                {meeting.scheduler
                                                                    ? `${meeting.scheduler.last_name}, ${meeting.scheduler.first_name}`
                                                                    : 'Unknown'}
                                                            </span>
                                                        </p>
                                                    </div>

                                                    <span
                                                        className={`shrink-0 px-2.5 py-1 rounded-full text-xs font-medium ${meetingStatusClasses(meeting.status)}`}
                                                    >
                                                        {meeting.status}
                                                    </span>
                                                </div>

                                                {/* Notes */}
                                                {meeting.notes && (
                                                    <div className="bg-slate-50 border border-slate-200 rounded-lg p-3">
                                                        <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">Notes</p>
                                                        <p className="text-sm text-slate-600 whitespace-pre-line">{meeting.notes}</p>
                                                    </div>
                                                )}

                                                {/* Meeting Actions */}
                                                {isActive && (
                                                    <div className="flex gap-2 pt-1">
                                                        <button
                                                            type="button"
                                                            onClick={() => updateMeeting(meeting.id, 'cancel')}
                                                            className="px-3 py-1.5 bg-red-600 text-white rounded-lg text-xs font-medium hover:bg-red-700 transition"
                                                        >
                                                            Cancel
                                                        </button>
                                                        <button
                                                            type="button"
                                                            onClick={() => updateMeeting(meeting.id, 'finish')}
                                                            className="px-3 py-1.5 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 transition"
                                                        >
                                                            Finish
                                                        </button>
                                                    </div>
                                                )}

                                                {/* Chat Toggle */}
                                                <div className="pt-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => toggleChat(meeting.id)}
                                                        className="text-xs font-medium text-blue-600 hover:text-blue-700 hover:underline"
                                                    >
                                                        {isChatOpen ? 'Hide Chat' : isActive ? 'Open Chat' : 'View Chat History'}
                                                    </button>
                                                </div>

                                                {/* Chat */}
                                                {isChatOpen && (
                                                    <div className="border border-slate-200 rounded-xl overflow-hidden">
                                                        {/* Chat Messages */}
                                                        <div className="bg-slate-50 p-3 max-h-64 overflow-y-auto space-y-2">
                                                            {meeting.chatMessages.length > 0 ? (
                                                                meeting.chatMessages.map((msg) => {
                                                                    const isMine = msg.sender_id === auth.user.id;

                                                                    return (
                                                                        <div key={msg.id} className={`flex ${isMine ? 'justify-end' : 'justify-start'}`}>
                                                                            <div
                                                                                className={`max-w-[80%] px-3 py-2 rounded-xl text-sm ${
                                                                                    isMine
                                                                                        ? 'bg-blue-600 text-white rounded-br-sm'
                                                                                        : 'bg-white border border-slate-200 text-slate-800 rounded-bl-sm'
                                                                                }`}
                                                                            >
                                                                                <p className="whitespace-pre-line wrap-break-word">{msg.message}</p>
                                                                                <span
                                                                                    className={`block text-[10px] mt-1 ${
                                                                                        isMine ? 'text-blue-100' : 'text-slate-400'
                                                                                    }`}
                                                                                >
                                                                                    {formatTime(msg.created_at)}
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    );
                                                                })
                                                            ) : (
                                                                <div className="py-6 text-center">
                                                                    <p className="text-xs text-slate-500">No messages yet.</p>
                                                                    {isActive && (
                                                                        <p className="text-[11px] text-slate-400 mt-1">Start the conversation below.</p>
                                                                    )}
                                                                </div>
                                                            )}
                                                        </div>

                                                        {/* Message Input — Active Only */}
                                                        {isActive && (
                                                            <form
                                                                onSubmit={(e) => {
                                                                    e.preventDefault();
                                                                    sendMessage(meeting);
                                                                }}
                                                                className="border-t border-slate-200 bg-white p-3 flex gap-2"
                                                            >
                                                                <input
                                                                    type="text"
                                                                    value={messageDrafts[meeting.id] ?? ''}
                                                                    onChange={(e) =>
                                                                        setMessageDrafts((prev) => ({ ...prev, [meeting.id]: e.target.value }))
                                                                    }
                                                                    placeholder="Type a message..."
                                                                    maxLength={500}
                                                                    className="flex-1 min-w-0 border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                />
                                                                <button
                                                                    type="submit"
                                                                    disabled={!(messageDrafts[meeting.id] ?? '').trim()}
                                                                    className="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white rounded-lg text-xs font-medium hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition"
                                                                >
                                                                    <Send className="w-3.5 h-3.5" />
                                                                    Send
                                                                </button>
                                                            </form>
                                                        )}
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })
                                ) : (
                                    <p className="text-slate-500">No meetings scheduled for this report.</p>
                                )}
                            </div>
                        </SectionCard>
                    </aside>
                </div>
            </div>

            {/* MODAL */}
            <Modal
                isOpen={scheduleOpen}
                onClose={() => setScheduleOpen(false)}
                title="Schedule Meeting"
                description="Pick a date and time, and leave a note for details."
                size="md"
                footer={
                    <button
                        type="submit"
                        form="schedule-meeting-form"
                        disabled={scheduleForm.processing}
                        className="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 disabled:opacity-50"
                    >
                        {scheduleForm.processing ? 'Submitting…' : 'Submit'}
                    </button>
                }
            >
                <form id="schedule-meeting-form" onSubmit={submitSchedule} className="space-y-4">
                    <div>
                        <label htmlFor="meeting_datetime" className="block text-sm font-medium text-slate-700 mb-1">
                            Choose date and time
                        </label>
                        <input
                            type="datetime-local"
                            id="meeting_datetime"
                            name="meeting_datetime"
                            min={formatMinDate()}
                            value={scheduleForm.data.meeting_datetime}
                            onChange={(e) => scheduleForm.setData('meeting_datetime', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        {scheduleForm.errors.meeting_datetime && (
                            <p className="mt-1 text-xs text-red-600">{scheduleForm.errors.meeting_datetime}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="note" className="block text-sm font-medium text-slate-700 mb-1">
                            Note
                        </label>
                        <textarea
                            id="note"
                            name="note"
                            rows={3}
                            value={scheduleForm.data.note}
                            onChange={(e) => scheduleForm.setData('note', e.target.value)}
                            className="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                    </div>
                </form>
            </Modal>

            {/* PREVIEW */}
            <Modal
                isOpen={!!preview}
                onClose={() => setPreview(null)}
                size="xl"
                showCloseButton={false}
                panelClassName="bg-transparent shadow-none ring-0"
                darkenBackground={true}
            >
                <div className="relative">
                    <button
                        type="button"
                        onClick={() => setPreview(null)}
                        className="absolute -top-10 right-0 text-white/80 hover:text-white transition"
                        aria-label="Close"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" className="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>

                    {preview?.type === 'image' ? (
                        <img src={preview.src} alt="" className="w-full max-h-[75vh] object-contain rounded-lg" />
                    ) : preview ? (
                        <video src={preview.src} controls autoPlay className="w-full max-h-[75vh] rounded-lg">
                            <source src={preview.src} type={preview.mime} />
                        </video>
                    ) : null}
                </div>
            </Modal>
        </AppLayout>
    );
}
