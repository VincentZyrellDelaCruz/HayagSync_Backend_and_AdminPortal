import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, Paginated, Report } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, CalendarClock, CheckCircle2, Clock3, FileText, Plus, ShieldAlert } from 'lucide-react';

interface ParentReport extends Report {
    parent_student?: { id: string; name: string; student_number: string } | null;
}

interface Props {
    reports: Paginated<ParentReport>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Incident Reports', href: '/reports' },
];

const statusClass = (status?: string) => {
    switch (status) {
        case 'Pending': return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
        case 'Under Investigation': return 'bg-orange-100 text-orange-700 ring-1 ring-orange-200';
        case 'Scheduled': return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
        case 'Escalated': return 'bg-purple-100 text-purple-700 ring-1 ring-purple-200';
        case 'Resolved': return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
        case 'Dismissed': return 'bg-rose-100 text-rose-700 ring-1 ring-rose-200';
        default: return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
    }
};

const statusIcon = (status?: string) => {
    switch (status) {
        case 'Resolved': return <CheckCircle2 className="h-4 w-4 text-emerald-500" />;
        case 'Escalated': return <ShieldAlert className="h-4 w-4 text-purple-500" />;
        case 'Pending': return <AlertTriangle className="h-4 w-4 text-amber-500" />;
        case 'Under Investigation': return <Clock3 className="h-4 w-4 text-orange-500" />;
        default: return <Clock3 className="h-4 w-4 text-slate-400" />;
    }
};

export default function ParentIndex({ reports }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="My Incident Reports" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-5xl px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                    <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">My Incident Reports</h1>
                            <p className="mt-1 text-sm text-slate-500">Review reports that you submitted and monitor their status.</p>
                        </div>

                        <Link
                            href={route('web.reports.create')}
                            className="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-700"
                        >
                            <Plus className="h-4 w-4" />
                            Report New Incident
                        </Link>
                    </div>

                    <section className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        {reports.data.length > 0 ? (
                            <div className="divide-y divide-slate-200">
                                {reports.data.map((report) => {
                                    const status = report.current_status?.status_name ?? 'Unknown';

                                    return (
                                        <Link key={report.id} href={route('web.reports.show', report.id)} className="block p-5 hover:bg-slate-50 sm:p-6">
                                            <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-2">
                                                        {statusIcon(status)}
                                                        <h2 className="truncate text-base font-semibold text-slate-900">{report.incident_title}</h2>
                                                        <span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold ${statusClass(status)}`}>{status}</span>
                                                    </div>

                                                    <div className="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-slate-500">
                                                        <span className="font-mono">{report.report_code}</span>
                                                        {report.category && <span>{report.category.category_name}</span>}
                                                        <span className="inline-flex items-center gap-1.5">
                                                            <CalendarClock className="h-3.5 w-3.5" />
                                                            {new Date(report.created_at).toLocaleDateString()}
                                                        </span>
                                                    </div>

                                                    {report.parent_student && (
                                                        <p className="mt-3 text-xs text-slate-500">
                                                            Related student: <span className="font-medium text-slate-700">{report.parent_student.name}</span>
                                                        </p>
                                                    )}
                                                </div>
                                            </div>
                                        </Link>
                                    );
                                })}
                            </div>
                        ) : (
                            <div className="px-5 py-16 text-center">
                                <div className="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100">
                                    <FileText className="h-6 w-6 text-slate-400" />
                                </div>
                                <h2 className="text-base font-semibold text-slate-700">No incident reports yet</h2>
                                <p className="mx-auto mt-1 max-w-md text-sm text-slate-500">
                                    Submit a report to document a bullying or student-safety concern involving your child.
                                </p>
                                <Link href={route('web.reports.create')} className="mt-5 inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                                    <Plus className="h-4 w-4" />
                                    Report New Incident
                                </Link>
                            </div>
                        )}

                        {reports.links.length > 3 && (
                            <div className="border-t border-slate-200 px-4 py-4 sm:px-5">
                                <Pagination links={reports.links} meta={{ from: reports.from, to: reports.to, total: reports.total }} />
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
