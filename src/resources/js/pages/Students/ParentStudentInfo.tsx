import AppLayout from '@/layouts/app-layout';
import { Student, StudentInfoProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, GraduationCap, ShieldCheck, UserRound } from 'lucide-react';


export default function ParentStudentInfo({ student }: StudentInfoProps) {
    const fullName = [student.first_name, student.middle_name, student.last_name, student.suffix]
        .filter(Boolean)
        .join(' ');

    const grade = student.latest_enrollment?.grade_section?.grade_level;
    const section = student.latest_enrollment?.grade_section?.section;
    const schoolYear = student.latest_enrollment?.grade_section?.school_year?.school_year;

    return (
        <AppLayout breadcrumbs={[{ title: 'Related Students', href: route('web.students.index') }, { title: fullName, href: '#' }]}>
            <Head title={`${fullName} - Related Student`} />

            <div className="min-h-full bg-slate-50/60">
                <div className="mx-auto w-full max-w-5xl space-y-6 px-4 py-5 sm:px-6 sm:py-6 lg:px-8 lg:py-8">

                    <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-6 sm:px-7">
                            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div className="flex items-center gap-4">
                                    {/* <div className="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                        <UserRound className="h-7 w-7" />
                                    </div> */}

                                    <div>
                                        <h1 className="mt-1 text-xl font-bold tracking-tight text-slate-900 sm:text-2xl">{fullName}</h1>
                                        <p className="mt-1 text-sm font-medium text-slate-500">{student.student_number}</p>
                                    </div>
                                </div>

                                <span className="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                    {student.status}
                                </span>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-3">
                            <InfoCard label="Grade Level" value={grade ?? 'Not available'} icon={GraduationCap} />
                            <InfoCard label="Section" value={section ?? 'Not available'} icon={GraduationCap} />
                            <InfoCard label="Academic Year" value={schoolYear ?? 'Not available'} icon={ShieldCheck} />
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function InfoCard({ label, value, icon: Icon }: { label: string; value: string; icon: typeof GraduationCap }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4">
            <div className="flex items-center gap-3">
                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-500">
                    <Icon className="h-4 w-4" />
                </div>

                <div className="min-w-0">
                    <p className="text-[11px] font-semibold uppercase tracking-wide text-slate-400">{label}</p>
                    <p className="mt-1 truncate text-sm font-semibold text-slate-800">{value}</p>
                </div>
            </div>
        </div>
    );
}
