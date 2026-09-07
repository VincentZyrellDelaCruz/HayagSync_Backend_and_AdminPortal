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
import {
    AlertTriangle,
    CalendarClock,
    CalendarSync,
    CheckCircle2,
    ClipboardList,
    Clock,
    Database,
    FileText,
    History,
    Image as ImageIcon,
    ImageOff,
    MapPin,
    MessageCircle,
    Play,
    Send,
    ShieldAlert,
    UserRound,
    Users,
    Video,
    X,
} from 'lucide-react';
import { FormEvent, useEffect, useRef, useState } from 'react';

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
    mime_type?: string | null;
    file_type?: string | null;
    file_size?: number | null;
    caption?: string | null;
    evidence_verification_state?: string | null;
    evidence_verification_details?: string | null;
}

type StudentInvolved = Pick<
    Student,
    'id' | 'first_name' | 'last_name' | 'middle_name' | 'suffix' | 'student_number' | 'gender' | 'email' | 'phone_number'
> & {
    pivot: ReportPivot & {
        finding?: string | null;
    };
};

interface MeetingParticipant {
    id: string;
    participant_role: string;
    attendance_status?: string | null;
    guest_name?: string | null;
    student?: Pick<Student, 'id' | 'first_name' | 'last_name' | 'student_number'> | null;
    user?: ReportUser | null;
}

interface ChatMessage {
    id: string;
    sender_id: string;
    message: string;
    created_at: string;
}

interface Meeting {
    id: string;
    meeting_date: string;
    meeting_type?: 'In-Person' | 'Virtual' | 'Both' | string;
    purpose?: string | null;
    status: 'Active' | 'Canceled' | 'Finished' | string;
    notes: string | null;
    scheduler: ReportUser | null;
    participants?: MeetingParticipant[];
    chatMessages: ChatMessage[];
}

interface CurrentAssignee {
    staff_number?: string | null;
    department?: string | null;
    user?: ReportUser | null;
}

interface ReportDetail extends Omit<
    SharedReport,
    'current_status' | 'category'
> {
    report_code?: string | null;
    incident_date: string | null;
    incident_time?: string | null;
    updated_at: string;
    category: ReportCategory | null;
    current_status: ReportStatus;
    latest_update: ReportUpdate | null;
    latest_assignment?: {
        id: string;
        assigned_to?: string | null;
        level: string | number;
        assigned_at?: string | null;
        ended_at?: string | null;
    } | null;
    report_evidences: Evidence[];
    students: StudentInvolved[];
    report_updates: ReportUpdate[];
    meetings: Meeting[];
    severity?: string | null;
    escalation_level?: number | null;
    current_level?: number | null;
    escalated_at?: string | null;
    closed_at?: string | null;
    current_assignee?: CurrentAssignee | null;
    can_act?: boolean;
    read_only?: boolean;
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

const timelineBadgeClasses = (status: string) => {
    switch (status) {
        case 'Pending':
            return 'bg-amber-100 text-amber-700';
        case 'Under Investigation':
            return 'bg-orange-100 text-orange-700';
        case 'Scheduled':
            return 'bg-blue-100 text-blue-700';
        case 'Escalated':
            return 'bg-purple-100 text-purple-700';
        case 'Resolved':
            return 'bg-emerald-100 text-emerald-700';
        case 'Dismissed':
            return 'bg-rose-100 text-rose-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
};

const meetingStatusClasses = (status: string) => {
    switch (status) {
        case 'Active':
            return 'bg-emerald-100 text-emerald-700';
        case 'Canceled':
            return 'bg-rose-100 text-rose-700';
        case 'Finished':
            return 'bg-blue-100 text-blue-700';
        default:
            return 'bg-slate-100 text-slate-700';
    }
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

const findingClasses = (finding?: string | null) => {
    switch ((finding ?? '').toLowerCase()) {
        case 'substantiated':
            return 'bg-emerald-100 text-emerald-700';
        case 'unsubstantiated':
            return 'bg-amber-100 text-amber-700';
        case 'cleared':
            return 'bg-blue-100 text-blue-700';
        case 'corroborated':
            return 'bg-purple-100 text-purple-700';
        default:
            return 'bg-slate-100 text-slate-600';
    }
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

const formatDateTime = (value: string) =>
    `${formatDate(value)} ${formatTime(value)}`;

const formatBytes = (value?: number | null) => {
    if (!value || value <= 0) {
        return null;
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    let size = value;
    let unit = 0;

    while (size >= 1024 && unit < units.length - 1) {
        size /= 1024;
        unit += 1;
    }

    return `${size.toFixed(size >= 10 || unit === 0 ? 0 : 1)} ${units[unit]}`;
};

const formatMinDate = (): string => {
    const today = new Date();
    const tomorrow = new Date(today);

    tomorrow.setDate(today.getDate() + 1);

    const year = tomorrow.getFullYear();
    const month = String(tomorrow.getMonth() + 1).padStart(2, '0');
    const day = String(tomorrow.getDate()).padStart(2, '0');
    const hours = String(tomorrow.getHours()).padStart(2, '0');
    const minutes = String(tomorrow.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

const studentFullName = (student: StudentInvolved) =>
    [
        `${student.last_name},`,
        student.first_name,
        student.middle_name,
        student.suffix,
    ]
        .filter(Boolean)
        .join(' ');

const storageUrl = (path: string) => {
    const normalizedPath = path.replace(/^\/+/, '');

    return normalizedPath.startsWith('storage/')
        ? `/${normalizedPath}`
        : `/storage/${normalizedPath}`;
};

const getEvidenceMimeType = (evidence: Evidence) =>
    evidence.mime_type ||
    evidence.file_type ||
    '';

const isVideoEvidence = (evidence: Evidence) => {
    const mime = getEvidenceMimeType(evidence);

    return mime.startsWith('video/');
};

const isImageEvidence = (evidence: Evidence) => {
    const mime = getEvidenceMimeType(evidence);

    return mime.startsWith('image/');
};

function EvidenceVideoPlayer({
    src,
    mime,
    title,
}: {
    src: string;
    mime: string;
    title: string;
}) {
    const videoRef = useRef<HTMLVideoElement | null>(null);

    useEffect(() => {
        return () => {
            if (videoRef.current) {
                videoRef.current.pause();
                videoRef.current.removeAttribute('src');
                videoRef.current.load();
            }
        };
    }, [src]);

    return (
        <video
            ref={videoRef}
            controls
            autoPlay
            playsInline
            preload="metadata"
            className="w-full max-h-[78vh] rounded-xl bg-black object-contain"
            aria-label={title}
        >
            <source src={src} type={mime} />
            Your browser does not support HTML5 video.
        </video>
    );
}

export default function Report({ report }: ReportShowProps) {
    const { auth } = usePage<PageProps>().props;

    const [scheduleOpen, setScheduleOpen] = useState(false);
    const [forwardOpen, setForwardOpen] = useState(false);
    const [resolveOpen, setResolveOpen] = useState(false);
    const [resolveConfirmOpen, setResolveConfirmOpen] = useState(false);
    const [dismissOpen, setDismissOpen] = useState(false);
    const [openChats, setOpenChats] = useState<Record<string, boolean>>({});
    const [messageDrafts, setMessageDrafts] = useState<Record<string, string>>({});
    const [preview, setPreview] = useState<{
        type: 'image' | 'video';
        src: string;
        mime: string;
        title: string;
    } | null>(null);

    const status = report.current_status?.status_name ?? 'No Status';

    const currentLevel = Number(
        report.current_level ??
        report.escalation_level ??
        report.latest_assignment?.level ??
        0,
    );

    const canAct = report.can_act === true && !['Resolved', 'Dismissed'].includes(status);

    const canSchedule = canAct;

    const canForward = canAct && currentLevel >= 1 && currentLevel < 4;

    const forwardLabel =
        currentLevel === 1 ? 'Forward to Principal'
            : (currentLevel === 2 ? 'Forward to Ministro'
                : (currentLevel === 3 ? 'Forward to OSD' : ''));

    const reporter = report.user
        ? `${report.user.last_name}, ${report.user.first_name}`
        : 'Unknown Reporter';

    const assignee = report.current_assignee?.user
        ? `${report.current_assignee.user.last_name}, ${report.current_assignee.user.first_name}`
        : null;

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Incident Report',
            href: route('web.reports.index'),
        },
        {
            title: report.incident_title,
            href: route('web.reports.show', report.id),
        },
    ];

    const scheduleForm = useForm({
        meeting_datetime: '',
        meeting_type: 'In-Person',
        purpose: '',
        note: '',
        participant_ids: [] as string[],
        guest_name: '',
        type: 'schedule',
    });

    const forwardForm = useForm({
        note: '',
    });

    const resolveForm = useForm({
        resolution_note: '',
        offender_id: '',
        discipline_action: '',
        discipline_notes: '',
    });

    const dismissForm = useForm({
        dismissal_note: '',
    });

    const submitForward = (e: FormEvent) => {
        e.preventDefault();

        forwardForm.post(
            route('web.reports.forward', report.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setForwardOpen(false);
                    forwardForm.reset();
                },
            },
        );
    };

    const submitResolve = (e: FormEvent) => {
        e.preventDefault();

        setResolveOpen(false);
        setResolveConfirmOpen(true);
    };

    const confirmResolve = () => {
        resolveForm.post(
            route('web.reports.resolve', report.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setResolveConfirmOpen(false);
                    resolveForm.reset();
                },
            },
        );
    };

    const submitDismiss = (e: FormEvent) => {
        e.preventDefault();

        dismissForm.post(
            route('web.reports.dismiss', report.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDismissOpen(false);
                    dismissForm.reset();
                },
            },
        );
    };

    const submitSchedule = (e: FormEvent) => {
        e.preventDefault();

        scheduleForm.put(
            route('web.reports.update', report.id),
            {
                preserveScroll: true,
                onSuccess: () => {
                    setScheduleOpen(false);
                    scheduleForm.reset();
                },
            },
        );
    };

    const toggleParticipant = (studentId: string) => {
        const selected = scheduleForm.data.participant_ids;

        if (selected.includes(studentId)) {
            scheduleForm.setData(
                'participant_ids',
                selected.filter((id) => id !== studentId),
            );

            return;
        }

        scheduleForm.setData(
            'participant_ids',
            [...selected, studentId],
        );
    };

    const toggleChat = (meetingId: string) => {
        setOpenChats((prev) => ({
            ...prev,
            [meetingId]: !prev[meetingId],
        }));
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
            route(
                'web.chat_messages.store',
                meeting.id,
            ),
            { message },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setMessageDrafts((prev) => ({
                        ...prev,
                        [meeting.id]: '',
                    }));
                },
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Incident Details" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-7xl space-y-6 px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                    {report.read_only && (
                        <div className="flex items-start gap-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <ShieldAlert className="mt-0.5 h-5 w-5 shrink-0 text-slate-400" />

                            <div>
                                <p className="text-sm font-semibold text-slate-700">
                                    Read-only case access
                                </p>

                                <p className="mt-0.5 text-xs leading-5 text-slate-500">
                                    You can review this incident and its history, but
                                    case actions are currently assigned to another
                                    authorized staff member.
                                </p>
                            </div>
                        </div>
                    )}

                    {/* HEADER */}
                    <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div className="p-5 sm:p-6 lg:p-7">
                            <div className="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                                <div className="min-w-0 flex-1">
                                    <div className="mb-3 flex flex-wrap items-center gap-2">
                                        <span
                                            className={`rounded-full px-3 py-1.5 text-xs font-semibold ${badgeClasses(
                                                status,
                                            )}`}
                                        >
                                            {status}
                                        </span>

                                        {report.severity && (
                                            <span
                                                className={`rounded-full px-3 py-1.5 text-xs font-semibold capitalize ${severityClasses(
                                                    report.severity,
                                                )}`}
                                            >
                                                {report.severity}
                                            </span>
                                        )}

                                        {['Pending', 'Under Investigation', 'Escalated'].includes(
                                            status,
                                        ) && (
                                            <span className="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700 ring-1 ring-red-100">
                                                <AlertTriangle className="h-3.5 w-3.5" />
                                                Needs Attention
                                            </span>
                                        )}
                                    </div>

                                    <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">
                                        {report.incident_title}
                                    </h1>

                                    <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-500">
                                        Detailed incident case overview,
                                        involved students, supporting evidence,
                                        case history, and scheduled meetings.
                                    </p>
                                </div>

                                <div className="flex shrink-0 flex-col gap-4 xl:items-end">
                                    <div className="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500 xl:justify-end">
                                        <span className="inline-flex items-center gap-1.5">
                                            <CalendarClock className="h-4 w-4 text-slate-400" />
                                            {formatDate(report.created_at)}
                                        </span>

                                        <span className="inline-flex items-center gap-1.5">
                                            <Clock className="h-4 w-4 text-slate-400" />
                                            {formatTime(report.created_at)}
                                        </span>
                                    </div>

                                    <div className="flex flex-wrap justify-start gap-2 xl:justify-end">
                                        {canSchedule && (
                                            <Button
                                                type="button"
                                                onClick={() => setScheduleOpen(true)}
                                                className="inline-flex items-center gap-1.5 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                                            >
                                                <CalendarSync className="h-4 w-4" />
                                                Schedule Meeting
                                            </Button>
                                        )}

                                        {canForward && (
                                            <Button
                                                type="button"
                                                onClick={() => setForwardOpen(true)}
                                                className="inline-flex items-center gap-1.5 rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-purple-700"
                                            >
                                                <Send className="h-4 w-4" />
                                                {forwardLabel}
                                            </Button>
                                        )}

                                        {canAct && (
                                            <>
                                                <Button
                                                    type="button"
                                                    onClick={() => setResolveOpen(true)}
                                                    className="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                                                >
                                                    <CheckCircle2 className="h-4 w-4" />
                                                    Resolve
                                                </Button>

                                                <Button
                                                    type="button"
                                                    onClick={() => setDismissOpen(true)}
                                                    className="inline-flex items-center gap-1.5 rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-rose-700"
                                                >
                                                    <X className="h-4 w-4" />
                                                    Dismiss
                                                </Button>
                                            </>
                                        )}
                                    </div>
                                </div>
                            </div>

                            {(report.report_code ||
                                report.escalation_level ||
                                assignee ||
                                report.escalated_at ||
                                report.closed_at) && (
                                <div className="mt-6 grid grid-cols-1 gap-3 border-t border-slate-200 pt-5 sm:grid-cols-2 lg:grid-cols-4">
                                    {report.report_code && (
                                        <InfoField
                                            label="Report Code"
                                            value={
                                                <span className="font-mono">
                                                    {report.report_code}
                                                </span>
                                            }
                                        />
                                    )}

                                    {report.escalation_level && (
                                        <InfoField
                                            label="Escalation Level"
                                            value={`Level ${report.escalation_level}`}
                                        />
                                    )}

                                    {assignee && (
                                        <InfoField
                                            label="Current Handler"
                                            value={assignee}
                                        />
                                    )}

                                    {report.escalated_at && (
                                        <InfoField
                                            label="Escalated At"
                                            value={formatDateTime(
                                                report.escalated_at,
                                            )}
                                        />
                                    )}

                                    {report.closed_at && (
                                        <InfoField
                                            label="Closed At"
                                            value={formatDateTime(
                                                report.closed_at,
                                            )}
                                        />
                                    )}
                                </div>
                            )}
                        </div>
                    </div>

                    {/* QUICK STATS */}
                    <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-blue-700">
                                    <Users className="h-4 w-4" />
                                </span>

                                <div>
                                    <p className="text-lg font-bold text-slate-800">
                                        {report.students.length}
                                    </p>

                                    <p className="text-xs text-slate-500">
                                        Student
                                        {report.students.length !== 1
                                            ? 's'
                                            : ''}{' '}
                                        Involved
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                                    <ImageIcon className="h-4 w-4" />
                                </span>

                                <div>
                                    <p className="text-lg font-bold text-slate-800">
                                        {report.report_evidences.length}
                                    </p>

                                    <p className="text-xs text-slate-500">
                                        Evidence File
                                        {report.report_evidences.length !== 1
                                            ? 's'
                                            : ''}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                                    <History className="h-4 w-4" />
                                </span>

                                <div>
                                    <p className="text-lg font-bold text-slate-800">
                                        {report.report_updates.length}
                                    </p>

                                    <p className="text-xs text-slate-500">
                                        Case Update
                                        {report.report_updates.length !== 1
                                            ? 's'
                                            : ''}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div className="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div className="flex items-center gap-3">
                                <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                                    <CalendarClock className="h-4 w-4" />
                                </span>

                                <div>
                                    <p className="text-lg font-bold text-slate-800">
                                        {report.meetings.length}
                                    </p>

                                    <p className="text-xs text-slate-500">
                                        Meeting
                                        {report.meetings.length !== 1
                                            ? 's'
                                            : ''}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* MAIN GRID */}
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        {/* MAIN CONTENT */}
                        <section className="space-y-6 lg:col-span-8">
                            {/* INCIDENT OVERVIEW */}
                            <SectionCard
                                title="Incident Overview"
                                icon={
                                    <ClipboardList className="h-4 w-4 text-slate-400" />
                                }
                            >
                                <div className="grid grid-cols-1 gap-5 p-5 sm:grid-cols-2 sm:p-6">
                                    <InfoField
                                        label="Reported By"
                                        value={reporter}
                                    />

                                    <InfoField
                                        label="Category"
                                        value={
                                            report.category
                                                ?.category_name ??
                                            'N/A'
                                        }
                                    />

                                    <InfoField
                                        label="Incident Date"
                                        value={
                                            report.incident_date
                                                ? formatDate(
                                                    report.incident_date,
                                                )
                                                : 'Not specified'
                                        }
                                    />

                                    <InfoField
                                        label="Incident Time"
                                        value={
                                            report.incident_time
                                            ? new Date(report.incident_time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                                            : 'Not specified'
                                        }
                                    />

                                    <InfoField
                                        label="Location"
                                        value={
                                            <span className="inline-flex items-center gap-1.5">
                                                <MapPin className="h-3.5 w-3.5 text-slate-400" />
                                                {report.location ??
                                                    'Not specified'}
                                            </span>
                                        }
                                    />

                                    {report.severity && (
                                        <InfoField
                                            label="Assessment"
                                            value={
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${severityClasses(
                                                        report.severity,
                                                    )}`}
                                                >
                                                    {report.severity}
                                                </span>
                                            }
                                        />
                                    )}
                                </div>
                            </SectionCard>

                            {/* EVIDENCES */}
                            <SectionCard
                                title="Evidences"
                                icon={
                                    <ImageIcon className="h-4 w-4 text-slate-400" />
                                }
                                right={
                                    <span className="text-xs text-slate-500">
                                        {report.report_evidences.length}{' '}
                                        file
                                        {report.report_evidences.length !== 1
                                            ? 's'
                                            : ''}
                                    </span>
                                }
                            >
                                <div className="p-5 sm:p-6">
                                    {report.report_evidences.length > 0 ? (
                                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                            {report.report_evidences.map(
                                                (evidence) => {
                                                    const mime =
                                                        getEvidenceMimeType(
                                                            evidence,
                                                        );
                                                    const isImage =
                                                        isImageEvidence(
                                                            evidence,
                                                        );
                                                    const isVideo =
                                                        isVideoEvidence(
                                                            evidence,
                                                        );
                                                    const src = isVideo
                                                        ? route(
                                                            'web.reports.evidence.stream',
                                                            evidence.id,
                                                        )
                                                        : storageUrl(
                                                            evidence.file_path,
                                                        );

                                                    return (
                                                        <button
                                                            key={evidence.id}
                                                            type="button"
                                                            onClick={() =>
                                                                setPreview({
                                                                    type: isImage
                                                                        ? 'image'
                                                                        : 'video',
                                                                    src,
                                                                    mime,
                                                                    title:
                                                                        evidence.file_name,
                                                                })
                                                            }
                                                            className="group overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md"
                                                        >
                                                            <div className="relative aspect-video overflow-hidden bg-slate-100">
                                                                {isImage ? (
                                                                    <img
                                                                        src={
                                                                            src
                                                                        }
                                                                        alt={
                                                                            evidence.file_name
                                                                        }
                                                                        loading="lazy"
                                                                        decoding="async"
                                                                        className="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                                                                    />
                                                                ) : (
                                                                    <div className="flex h-full w-full items-center justify-center bg-slate-900">
                                                                        {isVideo ? (
                                                                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur">
                                                                                <Play className="ml-0.5 h-5 w-5 fill-current" />
                                                                            </div>
                                                                        ) : (
                                                                            <Video className="h-10 w-10 text-white/70" />
                                                                        )}
                                                                    </div>
                                                                )}

                                                                {evidence.evidence_verification_state && (
                                                                    <span className="absolute left-3 top-3 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-slate-700 shadow-sm backdrop-blur">
                                                                        {
                                                                            evidence.evidence_verification_state
                                                                        }
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <div className="space-y-2 p-3.5">
                                                                <p className="truncate text-sm font-semibold text-slate-800">
                                                                    {
                                                                        evidence.file_name
                                                                    }
                                                                </p>

                                                                <div className="flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                                    {mime && (
                                                                        <span>
                                                                            {mime}
                                                                        </span>
                                                                    )}

                                                                    {formatBytes(
                                                                        evidence.file_size,
                                                                    ) && (
                                                                        <span>
                                                                            •{' '}
                                                                            {formatBytes(
                                                                                evidence.file_size,
                                                                            )}
                                                                        </span>
                                                                    )}
                                                                </div>

                                                                {evidence.caption && (
                                                                    <p className="line-clamp-2 text-xs leading-5 text-slate-500">
                                                                        {
                                                                            evidence.caption
                                                                        }
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </button>
                                                    );
                                                },
                                            )}
                                        </div>
                                    ) : (
                                        <EmptyState
                                            icon={
                                                <ImageOff className="h-6 w-6" />
                                            }
                                            title="No evidence uploaded"
                                            description="No image or video evidence was provided for this incident."
                                            className="px-0 py-6"
                                        />
                                    )}
                                </div>
                            </SectionCard>

                            {/* DESCRIPTION */}
                            <SectionCard
                                title="Incident Description"
                                icon={
                                    <FileText className="h-4 w-4 text-slate-400" />
                                }
                            >
                                <div className="p-5 sm:p-6">
                                    {report.description ? (
                                        <p className="whitespace-pre-line text-sm leading-7 text-slate-700">
                                            {report.description}
                                        </p>
                                    ) : (
                                        <p className="text-sm text-slate-500">
                                            No description provided for this
                                            incident.
                                        </p>
                                    )}
                                </div>
                            </SectionCard>

                            {/* STUDENTS INVOLVED */}
                            <SectionCard
                                title="Students Involved"
                                icon={
                                    <Users className="h-4 w-4 text-slate-400" />
                                }
                                right={
                                    <span className="text-xs text-slate-500">
                                        {report.students.length} student
                                        {report.students.length !== 1
                                            ? 's'
                                            : ''}
                                    </span>
                                }
                            >
                                <div className="divide-y divide-slate-200">
                                    {report.students.length > 0 ? (
                                        report.students.map((student) => (
                                            <div
                                                key={student.id}
                                                className="p-5 sm:p-6"
                                            >
                                                <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                                                    <div className="min-w-0 flex-1">
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <Link
                                                                href={route(
                                                                    'web.students.show',
                                                                    student.id,
                                                                )}
                                                                className="text-base font-semibold text-slate-800 transition hover:text-blue-600 hover:underline"
                                                            >
                                                                {studentFullName(
                                                                    student,
                                                                )}
                                                            </Link>

                                                            <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-700">
                                                                {student.pivot
                                                                    .involvement_type ??
                                                                    'Not specified'}
                                                            </span>

                                                            {student.pivot.finding && (
                                                                <span
                                                                    className={`rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${findingClasses(
                                                                        student
                                                                            .pivot
                                                                            .finding,
                                                                    )}`}
                                                                >
                                                                    {
                                                                        student
                                                                            .pivot
                                                                            .finding
                                                                    }
                                                                </span>
                                                            )}
                                                        </div>

                                                        <div className="mt-3 grid grid-cols-1 gap-3 text-sm text-slate-600 sm:grid-cols-2">
                                                            <div>
                                                                <span className="font-medium text-slate-700">
                                                                    Student No:
                                                                </span>{' '}
                                                                {
                                                                    student.student_number
                                                                }
                                                            </div>

                                                            <div>
                                                                <span className="font-medium text-slate-700">
                                                                    Gender:
                                                                </span>{' '}
                                                                {
                                                                    student.gender ??
                                                                    'N/A'
                                                                }
                                                            </div>

                                                            <div>
                                                                <span className="font-medium text-slate-700">
                                                                    Email:
                                                                </span>{' '}
                                                                {
                                                                    student.email ??
                                                                    'N/A'
                                                                }
                                                            </div>

                                                            <div>
                                                                <span className="font-medium text-slate-700">
                                                                    Phone:
                                                                </span>{' '}
                                                                {
                                                                    student.phone_number ??
                                                                    'N/A'
                                                                }
                                                            </div>
                                                        </div>

                                                        {student.pivot.notes && (
                                                            <div className="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                                                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                                    Involvement
                                                                    Notes
                                                                </p>

                                                                <p className="whitespace-pre-line text-sm leading-6 text-slate-700">
                                                                    {
                                                                        student
                                                                            .pivot
                                                                            .notes
                                                                    }
                                                                </p>
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        ))
                                    ) : (
                                        <EmptyState
                                            icon={
                                                <Users className="h-6 w-6" />
                                            }
                                            title="No students linked"
                                            description="No student records are associated with this incident yet."
                                        />
                                    )}
                                </div>
                            </SectionCard>

                            {/* TIMELINE / UPDATES */}
                            <SectionCard
                                title="Case Timeline"
                                icon={
                                    <History className="h-4 w-4 text-slate-400" />
                                }
                                right={
                                    <span className="text-xs text-slate-500">
                                        {report.report_updates.length} update
                                        {report.report_updates.length !== 1
                                            ? 's'
                                            : ''}
                                    </span>
                                }
                            >
                                <div className="p-5 sm:p-6">
                                    {report.report_updates.length > 0 ? (
                                        report.report_updates.map(
                                            (update, index) => {
                                                const updateStatus =
                                                    update.report_status
                                                        ?.status_name ??
                                                    'Unknown Status';
                                                const updater = update.user;
                                                const updaterName = updater
                                                    ? `${updater.first_name} ${updater.last_name}`.trim()
                                                    : 'Unknown Staff';
                                                const isLast =
                                                    index ===
                                                    report.report_updates
                                                        .length -
                                                        1;

                                                return (
                                                    <div
                                                        key={update.id}
                                                        className={`relative pl-8 ${
                                                            !isLast
                                                                ? 'pb-8'
                                                                : ''
                                                        }`}
                                                    >
                                                        {!isLast && (
                                                            <div className="absolute bottom-0 left-2.75 top-6 w-0.5 bg-slate-200" />
                                                        )}

                                                        <div className="absolute left-0 top-1 flex h-6 w-6 items-center justify-center rounded-full border-4 border-white bg-slate-900 shadow-sm">
                                                            <span className="h-1.5 w-1.5 rounded-full bg-white" />
                                                        </div>

                                                        <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4 sm:p-5">
                                                            <div className="mb-3 flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                                <div className="min-w-0">
                                                                    <div className="flex flex-wrap items-center gap-2">
                                                                        <span
                                                                            className={`rounded-full px-2.5 py-1 text-xs font-medium ${timelineBadgeClasses(
                                                                                updateStatus,
                                                                            )}`}
                                                                        >
                                                                            {
                                                                                updateStatus
                                                                            }
                                                                        </span>

                                                                        {index ===
                                                                            0 && (
                                                                            <span className="rounded-full bg-slate-900 px-2.5 py-1 text-xs font-semibold text-white">
                                                                                Latest
                                                                            </span>
                                                                        )}
                                                                    </div>

                                                                    <h3 className="mt-2 text-sm font-semibold text-slate-800">
                                                                        Updated
                                                                        by{' '}
                                                                        {
                                                                            updaterName
                                                                        }
                                                                    </h3>

                                                                    <p className="mt-1 text-xs text-slate-500">
                                                                        {updater?.staff ? (
                                                                            <>
                                                                                Staff
                                                                                No:{' '}
                                                                                {updater
                                                                                    .staff
                                                                                    .staff_number ??
                                                                                    'N/A'}

                                                                                {updater
                                                                                    .staff
                                                                                    .department && (
                                                                                    <>
                                                                                        {' '}
                                                                                        •{' '}
                                                                                        {
                                                                                            updater
                                                                                                .staff
                                                                                                .department
                                                                                        }
                                                                                    </>
                                                                                )}
                                                                            </>
                                                                        ) : (
                                                                            'Staff record not found'
                                                                        )}
                                                                    </p>
                                                                </div>

                                                                <div className="shrink-0 text-xs text-slate-500 sm:text-right">
                                                                    <p className="font-medium text-slate-700">
                                                                        {formatDate(
                                                                            update.created_at,
                                                                        )}
                                                                    </p>

                                                                    <p>
                                                                        {formatTime(
                                                                            update.created_at,
                                                                        )}
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                                    Review Note
                                                                </p>

                                                                {update.note ? (
                                                                    <p className="whitespace-pre-line text-sm leading-6 text-slate-700">
                                                                        {
                                                                            update.note
                                                                        }
                                                                    </p>
                                                                ) : (
                                                                    <p className="text-sm italic text-slate-500">
                                                                        No note
                                                                        provided
                                                                        for this
                                                                        update.
                                                                    </p>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </div>
                                                );
                                            },
                                        )
                                    ) : (
                                        <EmptyState
                                            icon={
                                                <History className="h-6 w-6" />
                                            }
                                            title="No incident updates yet"
                                            description="This case has not been reviewed or updated yet."
                                        />
                                    )}
                                </div>
                            </SectionCard>
                        </section>

                        {/* RIGHT SIDEBAR */}
                        <aside className="space-y-6 self-start lg:sticky lg:top-6 lg:col-span-4">
                            {/* METADATA */}
                            <SectionCard
                                title="Record Metadata"
                                icon={
                                    <Database className="h-4 w-4 text-slate-400" />
                                }
                            >
                                <div className="space-y-4 p-5 sm:p-6">
                                    <InfoField
                                        label="Incident ID"
                                        value={
                                            <span className="break-all">
                                                {report.id}
                                            </span>
                                        }
                                    />

                                    {report.report_code && (
                                        <InfoField
                                            label="Report Code"
                                            value={
                                                <span className="font-mono">
                                                    {
                                                        report.report_code
                                                    }
                                                </span>
                                            }
                                        />
                                    )}

                                    <InfoField
                                        label="Created At"
                                        value={formatDateTime(
                                            report.created_at,
                                        )}
                                    />

                                    <InfoField
                                        label="Last Updated"
                                        value={formatDateTime(
                                            report.latest_update
                                                ?.created_at ??
                                            report.updated_at,
                                        )}
                                    />

                                    <InfoField
                                        label="Total Updates"
                                        value={report.report_updates.length}
                                    />

                                    {report.current_status && (
                                        <InfoField
                                            label="Current Status"
                                            value={
                                                <span
                                                    className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${badgeClasses(
                                                        report
                                                            .current_status
                                                            .status_name,
                                                    )}`}
                                                >
                                                    {
                                                        report
                                                            .current_status
                                                            .status_name
                                                    }
                                                </span>
                                            }
                                        />
                                    )}
                                </div>
                            </SectionCard>

                            {/* MEETINGS */}
                            <SectionCard
                                title="Meetings"
                                icon={
                                    <CalendarClock className="h-4 w-4 text-slate-400" />
                                }
                                right={
                                    <span className="text-xs text-slate-500">
                                        {report.meetings.length}
                                    </span>
                                }
                            >
                                <div className="space-y-4 p-5 sm:p-6">
                                    {report.meetings.length > 0 ? (
                                        report.meetings.map((meeting) => {
                                            const isChatOpen =
                                                !!openChats[meeting.id];
                                            const isActive =
                                                meeting.status === 'Active';

                                            return (
                                                <div
                                                    key={meeting.id}
                                                    className="space-y-3 rounded-2xl border border-slate-200 p-4"
                                                >
                                                    {/* Meeting Header */}
                                                    <div className="flex items-start justify-between gap-3">
                                                        <div className="min-w-0">
                                                            <p className="font-semibold text-slate-800">
                                                                {formatDateTime(
                                                                    meeting.meeting_date,
                                                                )}
                                                            </p>

                                                            <div className="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-500">
                                                                <span>
                                                                    Scheduled
                                                                    By:{' '}
                                                                    <span className="text-slate-700">
                                                                        {meeting.scheduler
                                                                            ? `${meeting.scheduler.last_name}, ${meeting.scheduler.first_name}`
                                                                            : 'Unknown'}
                                                                    </span>
                                                                </span>

                                                                {meeting.meeting_type && (
                                                                    <span>
                                                                        Type:{' '}
                                                                        <span className="font-medium text-slate-700">
                                                                            {
                                                                                meeting.meeting_type
                                                                            }
                                                                        </span>
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>

                                                        <span
                                                            className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ${meetingStatusClasses(
                                                                meeting.status,
                                                            )}`}
                                                        >
                                                            {meeting.status}
                                                        </span>
                                                    </div>

                                                    {meeting.purpose && (
                                                        <div className="rounded-xl bg-slate-50 p-3">
                                                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                                Purpose
                                                            </p>

                                                            <p className="whitespace-pre-line text-sm leading-6 text-slate-700">
                                                                {
                                                                    meeting.purpose
                                                                }
                                                            </p>
                                                        </div>
                                                    )}

                                                    {/* Participants */}
                                                    {meeting.participants &&
                                                        meeting.participants
                                                            .length >
                                                        0 && (
                                                            <div className="rounded-xl border border-slate-200 bg-white p-3">
                                                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                                    Participants
                                                                </p>

                                                                <div className="space-y-2">
                                                                    {meeting.participants.map(
                                                                        (
                                                                            participant,
                                                                        ) => {
                                                                            const participantName =
                                                                                participant.student
                                                                                    ? `${participant.student.last_name}, ${participant.student.first_name}`
                                                                                    : participant.user
                                                                                        ? `${participant.user.last_name}, ${participant.user.first_name}`
                                                                                        : participant.guest_name ??
                                                                                        'Unknown';

                                                                            return (
                                                                                <div
                                                                                    key={
                                                                                        participant.id
                                                                                    }
                                                                                    className="flex items-center justify-between gap-3 rounded-lg bg-slate-50 px-3 py-2"
                                                                                >
                                                                                    <div className="flex min-w-0 items-center gap-2">
                                                                                        <UserRound className="h-3.5 w-3.5 shrink-0 text-slate-400" />

                                                                                        <div className="min-w-0">
                                                                                            <p className="truncate text-xs font-medium text-slate-700">
                                                                                                {
                                                                                                    participantName
                                                                                                }
                                                                                            </p>

                                                                                            <p className="truncate text-[11px] text-slate-400">
                                                                                                {
                                                                                                    participant.participant_role
                                                                                                }
                                                                                            </p>
                                                                                        </div>
                                                                                    </div>

                                                                                    {participant.attendance_status && (
                                                                                        <span className="shrink-0 text-[10px] font-medium capitalize text-slate-500">
                                                                                            {
                                                                                                participant.attendance_status
                                                                                            }
                                                                                        </span>
                                                                                    )}
                                                                                </div>
                                                                            );
                                                                        },
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}

                                                    {/* Notes */}
                                                    {meeting.notes && (
                                                        <div className="rounded-xl border border-slate-200 bg-slate-50 p-3">
                                                            <p className="mb-1 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                                                Notes
                                                            </p>

                                                            <p className="whitespace-pre-line text-sm leading-6 text-slate-600">
                                                                {
                                                                    meeting.notes
                                                                }
                                                            </p>
                                                        </div>
                                                    )}

                                                    {/* Chat Toggle */}
                                                    <div className="pt-1">
                                                        <button
                                                            type="button"
                                                            onClick={() =>
                                                                toggleChat(
                                                                    meeting.id,
                                                                )
                                                            }
                                                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                                                        >
                                                            <MessageCircle className="h-3.5 w-3.5" />
                                                            {isChatOpen
                                                                ? 'Hide Chat'
                                                                : isActive
                                                                  ? 'Open Chat'
                                                                  : 'View Chat History'}
                                                        </button>
                                                    </div>

                                                    {/* Chat */}
                                                    {isChatOpen && (
                                                        <div className="overflow-hidden rounded-xl border border-slate-200">
                                                            {/* Chat Messages */}
                                                            <div className="max-h-64 space-y-2 overflow-y-auto bg-slate-50 p-3">
                                                                {meeting.chatMessages.length >
                                                                0 ? (
                                                                    meeting.chatMessages.map(
                                                                        (
                                                                            msg,
                                                                        ) => {
                                                                            const isMine =
                                                                                msg.sender_id ===
                                                                                auth
                                                                                    .user
                                                                                    .id;

                                                                            return (
                                                                                <div
                                                                                    key={
                                                                                        msg.id
                                                                                    }
                                                                                    className={`flex ${
                                                                                        isMine
                                                                                            ? 'justify-end'
                                                                                            : 'justify-start'
                                                                                    }`}
                                                                                >
                                                                                    <div
                                                                                        className={`max-w-[85%] rounded-2xl px-3 py-2 text-sm ${
                                                                                            isMine
                                                                                                ? 'rounded-br-sm bg-blue-600 text-white'
                                                                                                : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800'
                                                                                        }`}
                                                                                    >
                                                                                        <p className="whitespace-pre-line wrap-break-word">
                                                                                            {
                                                                                                msg.message
                                                                                            }
                                                                                        </p>

                                                                                        <span
                                                                                            className={`mt-1 block text-[10px] ${
                                                                                                isMine
                                                                                                    ? 'text-blue-100'
                                                                                                    : 'text-slate-400'
                                                                                            }`}
                                                                                        >
                                                                                            {formatTime(
                                                                                                msg.created_at,
                                                                                            )}
                                                                                        </span>
                                                                                    </div>
                                                                                </div>
                                                                            );
                                                                        },
                                                                    )
                                                                ) : (
                                                                    <div className="py-6 text-center">
                                                                        <p className="text-xs text-slate-500">
                                                                            No
                                                                            messages
                                                                            yet.
                                                                        </p>

                                                                        {isActive && (
                                                                            <p className="mt-1 text-[11px] text-slate-400">
                                                                                Start
                                                                                the
                                                                                conversation
                                                                                below.
                                                                            </p>
                                                                        )}
                                                                    </div>
                                                                )}
                                                            </div>

                                                            {/* Message Input — Active Only */}
                                                            {isActive && (
                                                                <form
                                                                    onSubmit={(
                                                                        e,
                                                                    ) => {
                                                                        e.preventDefault();
                                                                        sendMessage(
                                                                            meeting,
                                                                        );
                                                                    }}
                                                                    className="flex gap-2 border-t border-slate-200 bg-white p-3"
                                                                >
                                                                    <input
                                                                        type="text"
                                                                        value={
                                                                            messageDrafts[
                                                                                meeting
                                                                                    .id
                                                                            ] ??
                                                                            ''
                                                                        }
                                                                        onChange={(
                                                                            e,
                                                                        ) =>
                                                                            setMessageDrafts(
                                                                                (
                                                                                    prev,
                                                                                ) => ({
                                                                                    ...prev,
                                                                                    [meeting.id]:
                                                                                        e.target
                                                                                            .value,
                                                                                }),
                                                                            )
                                                                        }
                                                                        placeholder="Type a message..."
                                                                        maxLength={
                                                                            500
                                                                        }
                                                                        className="min-w-0 flex-1 rounded-xl border border-slate-200 px-3 py-2 text-sm text-slate-800 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                                                    />

                                                                    <button
                                                                        type="submit"
                                                                        disabled={
                                                                            !(
                                                                                messageDrafts[
                                                                                    meeting
                                                                                        .id
                                                                                ] ??
                                                                                ''
                                                                            ).trim()
                                                                        }
                                                                        className="inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                                    >
                                                                        <Send className="h-3.5 w-3.5" />
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
                                        <EmptyState
                                            icon={
                                                <CalendarClock className="h-6 w-6" />
                                            }
                                            title="No meetings scheduled"
                                            description="No conferences or meetings have been scheduled for this incident."
                                            className="px-0 py-6"
                                        />
                                    )}
                                </div>
                            </SectionCard>
                        </aside>
                    </div>
                </div>
            </div>

            {/* MODAL */}
            <Modal
                isOpen={scheduleOpen}
                onClose={() => setScheduleOpen(false)}
                title="Schedule Meeting"
                description="Choose the meeting details and participants for this case."
                size="lg"
                footer={
                    <button
                        type="submit"
                        form="schedule-meeting-form"
                        disabled={scheduleForm.processing}
                        className="rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {scheduleForm.processing
                            ? 'Submitting…'
                            : 'Schedule Meeting'}
                    </button>
                }
            >
                <form
                    id="schedule-meeting-form"
                    onSubmit={submitSchedule}
                    className="space-y-5"
                >
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label
                                htmlFor="meeting_datetime"
                                className="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Date and time
                            </label>

                            <input
                                type="datetime-local"
                                id="meeting_datetime"
                                name="meeting_datetime"
                                min={formatMinDate()}
                                value={
                                    scheduleForm.data.meeting_datetime
                                }
                                onChange={(e) =>
                                    scheduleForm.setData(
                                        'meeting_datetime',
                                        e.target.value,
                                    )
                                }
                                className="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none text-black focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            />

                            {scheduleForm.errors.meeting_datetime && (
                                <p className="mt-1 text-xs text-red-600">
                                    {
                                        scheduleForm.errors
                                            .meeting_datetime
                                    }
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="meeting_type"
                                className="mb-1.5 block text-sm font-medium text-slate-700"
                            >
                                Meeting type
                            </label>

                            <select
                                id="meeting_type"
                                value={
                                    scheduleForm.data.meeting_type
                                }
                                onChange={(e) =>
                                    scheduleForm.setData(
                                        'meeting_type',
                                        e.target.value,
                                    )
                                }
                                className="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                <option value="In-Person">
                                    In-Person
                                </option>
                                <option value="Virtual">
                                    Virtual
                                </option>
                                <option value="Both">
                                    Both
                                </option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="purpose"
                            className="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Purpose
                        </label>

                        <textarea
                            id="purpose"
                            rows={3}
                            value={
                                scheduleForm.data.purpose
                            }
                            onChange={(e) =>
                                scheduleForm.setData(
                                    'purpose',
                                    e.target.value,
                                )
                            }
                            placeholder="e.g. Parent conference, student interview, case review..."
                            className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm text-black outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />

                        {scheduleForm.errors.purpose && (
                            <p className="mt-1 text-xs text-red-600">
                                {scheduleForm.errors.purpose}
                            </p>
                        )}
                    </div>

                    <div>
                        <div className="mb-2 flex items-center justify-between gap-3">
                            <label className="text-sm font-medium text-slate-700">
                                Participants
                            </label>

                            <span className="text-xs text-slate-400">
                                {
                                    scheduleForm.data
                                        .participant_ids.length
                                }{' '}
                                selected
                            </span>
                        </div>

                        <div className="max-h-60 space-y-2 overflow-y-auto rounded-2xl border border-slate-200 p-3">
                            {report.students.length > 0 ? (
                                report.students.map((student) => {
                                    const selected =
                                        scheduleForm.data.participant_ids.includes(
                                            student.id,
                                        );

                                    return (
                                        <label
                                            key={student.id}
                                            className={`flex cursor-pointer items-center gap-3 rounded-xl border px-3 py-3 transition ${
                                                selected
                                                    ? 'border-blue-200 bg-blue-50'
                                                    : 'border-transparent bg-slate-50 hover:border-slate-200 hover:bg-white'
                                            }`}
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selected}
                                                onChange={() =>
                                                    toggleParticipant(
                                                        student.id,
                                                    )
                                                }
                                                className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                            />

                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-medium text-slate-800">
                                                    {studentFullName(
                                                        student,
                                                    )}
                                                </p>

                                                <p className="text-xs text-slate-500">
                                                    {
                                                        student
                                                            .pivot
                                                            .involvement_type
                                                    }
                                                </p>
                                            </div>
                                        </label>
                                    );
                                })
                            ) : (
                                <p className="px-2 py-5 text-center text-sm text-slate-500">
                                    No students are linked to this incident.
                                </p>
                            )}
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="guest_name"
                            className="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Guest participant
                        </label>

                        <input
                            id="guest_name"
                            type="text"
                            value={scheduleForm.data.guest_name}
                            onChange={(e) =>
                                scheduleForm.setData(
                                    'guest_name',
                                    e.target.value,
                                )
                            }
                            placeholder="Optional counselor, external attendee, or other guest"
                            className="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </div>

                    <div>
                        <label
                            htmlFor="note"
                            className="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Meeting notes
                        </label>

                        <textarea
                            id="note"
                            rows={3}
                            value={scheduleForm.data.note}
                            onChange={(e) =>
                                scheduleForm.setData(
                                    'note',
                                    e.target.value,
                                )
                            }
                            placeholder="Optional notes or instructions..."
                            className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </div>
                </form>
            </Modal>

            {/* FORWARD */}
            <Modal
                isOpen={forwardOpen}
                onClose={() => setForwardOpen(false)}
                title={forwardLabel}
                description="Provide a brief reason for escalating this incident to the next authorized staff level."
                size="lg"
                footer={
                    <>
                        <button
                            type="button"
                            onClick={() => setForwardOpen(false)}
                            className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            form="forward-report-form"
                            disabled={forwardForm.processing}
                            className="rounded-xl bg-purple-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {forwardForm.processing
                                ? 'Forwarding…'
                                : 'Confirm Forward'}
                        </button>
                    </>
                }
            >
                <form
                    id="forward-report-form"
                    onSubmit={submitForward}
                    className="space-y-4"
                >
                    <div className="rounded-2xl border border-purple-100 bg-purple-50 p-4">
                        <div className="flex items-start gap-3">
                            <ShieldAlert className="mt-0.5 h-5 w-5 shrink-0 text-purple-600" />

                            <div>
                                <p className="text-sm font-semibold text-purple-900">
                                    Level {currentLevel} → Level {currentLevel + 1}
                                </p>

                                <p className="mt-1 text-xs leading-5 text-purple-700">
                                    The current case handler will retain read-only
                                    access after the case is escalated.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label
                            htmlFor="forward_note"
                            className="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Reason for escalation
                        </label>

                        <textarea
                            id="forward_note"
                            rows={5}
                            value={forwardForm.data.note}
                            onChange={(e) =>
                                forwardForm.setData(
                                    'note',
                                    e.target.value,
                                )
                            }
                            placeholder="Explain why the case requires the next level of review..."
                            className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm leading-6 text-slate-800 outline-none focus:border-purple-500 focus:ring-2 focus:ring-purple-100"
                        />

                        {forwardForm.errors.note && (
                            <p className="mt-1 text-xs text-red-600">
                                {forwardForm.errors.note}
                            </p>
                        )}
                    </div>
                </form>
            </Modal>

            {/* RESOLVE */}
            <Modal
                isOpen={resolveOpen}
                onClose={() => setResolveOpen(false)}
                title="Resolve Incident Report"
                description="Record the resolution and any final disciplinary action required for this case."
                size="lg"
                footer={
                    <>
                        <button
                            type="button"
                            onClick={() => setResolveOpen(false)}
                            className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            form="resolve-report-form"
                            className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700"
                        >
                            Continue
                        </button>
                    </>
                }
            >
                <form
                    id="resolve-report-form"
                    onSubmit={submitResolve}
                    className="space-y-5"
                >
                    {currentLevel === 4 && (
                        <div className="space-y-4 rounded-2xl border border-red-100 bg-red-50/60 p-4">
                            <div className="flex items-start gap-3">
                                <ShieldAlert className="mt-0.5 h-5 w-5 shrink-0 text-red-600" />

                                <div>
                                    <p className="text-sm font-semibold text-red-900">
                                        OSD disciplinary action
                                    </p>

                                    <p className="mt-1 text-xs leading-5 text-red-700">
                                        Select the confirmed offender and record
                                        the disciplinary action to be imposed.
                                    </p>
                                </div>
                            </div>

                            <div>
                                <label
                                    htmlFor="offender_id"
                                    className="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Confirmed offender
                                </label>

                                <select
                                    id="offender_id"
                                    value={resolveForm.data.offender_id}
                                    onChange={(e) =>
                                        resolveForm.setData(
                                            'offender_id',
                                            e.target.value,
                                        )
                                    }
                                    className="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 text-black"
                                >
                                    <option value="">
                                        Select a substantiated offender
                                    </option>

                                    {report.students
                                        .filter((student) =>
                                            ['offender', 'Offender'].includes(
                                                String(
                                                    student.pivot.involvement_type,
                                                ),
                                            ),
                                        )
                                        .map((student) => (
                                            <option
                                                key={student.id}
                                                value={student.id}
                                            >
                                                {studentFullName(student)} —{' '}
                                                {student.student_number}
                                            </option>
                                        ))}
                                </select>

                                {resolveForm.errors.offender_id && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {resolveForm.errors.offender_id}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="discipline_action"
                                    className="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Disciplinary action
                                </label>

                                <input
                                    id="discipline_action"
                                    type="text"
                                    value={resolveForm.data.discipline_action}
                                    onChange={(e) =>
                                        resolveForm.setData(
                                            'discipline_action',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Written warning, suspension, other authorized sanction"
                                    className="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 text-slate-700"
                                />

                                {resolveForm.errors.discipline_action && (
                                    <p className="mt-1 text-xs text-red-600">
                                        {resolveForm.errors.discipline_action}
                                    </p>
                                )}
                            </div>

                            <div>
                                <label
                                    htmlFor="discipline_notes"
                                    className="mb-1.5 block text-sm font-medium text-slate-700"
                                >
                                    Disciplinary notes
                                </label>

                                <textarea
                                    id="discipline_notes"
                                    rows={3}
                                    value={resolveForm.data.discipline_notes}
                                    onChange={(e) =>
                                        resolveForm.setData(
                                            'discipline_notes',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="Optional details supporting the disciplinary action..."
                                    className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100 text-slate-700"
                                />
                            </div>
                        </div>
                    )}

                    <div>
                        <label
                            htmlFor="resolution_note"
                            className="mb-1.5 block text-sm font-medium text-slate-700"
                        >
                            Resolution summary
                        </label>

                        <textarea
                            id="resolution_note"
                            rows={5}
                            value={resolveForm.data.resolution_note}
                            onChange={(e) =>
                                resolveForm.setData(
                                    'resolution_note',
                                    e.target.value,
                                )
                            }
                            placeholder="Describe how the case was handled and why it is ready to be resolved..."
                            className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm leading-6 text-slate-800 outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-100"
                        />

                        {resolveForm.errors.resolution_note && (
                            <p className="mt-1 text-xs text-red-600">
                                {resolveForm.errors.resolution_note}
                            </p>
                        )}
                    </div>
                </form>
            </Modal>

            <Modal
                isOpen={resolveConfirmOpen}
                onClose={() => setResolveConfirmOpen(false)}
                title="Confirm Resolution"
                description="Resolving the incident will close the current case workflow and end the active assignment."
                size="md"
                footer={
                    <>
                        <button
                            type="button"
                            onClick={() => {
                                setResolveConfirmOpen(false);
                                setResolveOpen(true);
                            }}
                            className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Back
                        </button>

                        <button
                            type="button"
                            onClick={confirmResolve}
                            disabled={resolveForm.processing}
                            className="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            <CheckCircle2 className="h-4 w-4" />
                            {resolveForm.processing
                                ? 'Resolving…'
                                : 'Confirm Resolution'}
                        </button>
                    </>
                }
            >
                <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                    <p className="text-sm leading-6 text-emerald-900">
                        The report will be marked as <strong>Resolved</strong>.
                        The case history and any recorded disciplinary action
                        will remain available for authorized review.
                    </p>
                </div>
            </Modal>

            {/* DISMISS */}
            <Modal
                isOpen={dismissOpen}
                onClose={() => setDismissOpen(false)}
                title="Dismiss Incident Report"
                description="Record why the complaint is being dismissed, such as insufficient evidence or an unsubstantiated report."
                size="lg"
                footer={
                    <>
                        <button
                            type="button"
                            onClick={() => setDismissOpen(false)}
                            className="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            form="dismiss-report-form"
                            disabled={dismissForm.processing}
                            className="rounded-xl bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {dismissForm.processing
                                ? 'Dismissing…'
                                : 'Confirm Dismissal'}
                        </button>
                    </>
                }
            >
                <form
                    id="dismiss-report-form"
                    onSubmit={submitDismiss}
                >
                    <label
                        htmlFor="dismissal_note"
                        className="mb-1.5 block text-sm font-medium text-slate-700"
                    >
                        Reason / Note
                    </label>

                    <textarea
                        id="dismissal_note"
                        rows={6}
                        value={dismissForm.data.dismissal_note}
                        onChange={(e) =>
                            dismissForm.setData(
                                'dismissal_note',
                                e.target.value,
                            )
                        }
                        placeholder="State why the complaint is being dismissed..."
                        className="w-full resize-y rounded-xl border border-slate-300 px-3 py-2.5 text-sm leading-6 text-slate-800 outline-none focus:border-rose-500 focus:ring-2 focus:ring-rose-100"
                    />

                    {dismissForm.errors.dismissal_note && (
                        <p className="mt-1 text-xs text-red-600">
                            {dismissForm.errors.dismissal_note}
                        </p>
                    )}
                </form>
            </Modal>

            {/* PREVIEW */}
            <Modal
                isOpen={!!preview}
                onClose={() => setPreview(null)}
                size="xl"
                showCloseButton={false}
                panelClassName="bg-transparent shadow-none ring-0"
                darkenBackground
            >
                <div className="relative">
                    <button
                        type="button"
                        onClick={() => setPreview(null)}
                        className="absolute -top-10 right-0 text-white/80 transition hover:text-white"
                        aria-label="Close"
                    >
                        <X className="h-7 w-7" />
                    </button>

                    {preview?.type === 'image' ? (
                        <img
                            src={preview.src}
                            alt={preview.title}
                            loading="eager"
                            decoding="async"
                            className="max-h-[78vh] w-full rounded-xl object-contain"
                        />
                    ) : preview ? (
                        <EvidenceVideoPlayer
                            src={preview.src}
                            mime={preview.mime}
                            title={preview.title}
                        />
                    ) : null}
                </div>
            </Modal>
        </AppLayout>
    );
}
