import Modal from '@/components/ui/modal';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { SectionCard } from '@/components/ui/section-card';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, FileAudio, FileImage, FileText, FileVideo, Info, Plus, Search, ShieldAlert, Trash2, Upload, UserRound, Users, X } from 'lucide-react';
import { FormEvent, useEffect, useState } from 'react';

interface Category {
    id: number;
    category_name: string;
    description?: string | null;
}

interface RelatedStudent {
    id: string;
    student_number: string;
    first_name: string;
    last_name: string;
    middle_name?: string | null;
    suffix?: string | null;
    grade_level?: string | null;
    section?: string | null;
}

interface SearchStudent {
    id: string;
    student_number: string;
    first_name: string;
    last_name: string;
    grade_level?: string | null;
    section?: string | null;
}

interface EvidenceItem {
    file: File;
    caption: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'My Incident Reports', href: route('web.reports.index') },
    { title: 'Report New Incident', href: route('web.reports.create') },
];

const MAX_FILES = 5;
const MAX_SIZE = 50 * 1024 * 1024;

const acceptedTypes = [
    'image/jpeg', 'image/png', 'image/webp',
    'video/mp4', 'video/webm',
    'audio/mpeg', 'audio/wav', 'audio/x-wav', 'audio/mp4', 'audio/x-m4a',
];

const fullName = (student: { first_name: string; last_name: string }) => `${student.first_name} ${student.last_name}`.trim();

export default function CreateReport({ categories, relatedStudents }: {
    categories: Category[];
    relatedStudents: RelatedStudent[];
}) {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [errorMessage, setErrorMessage] = useState('');
    const [offenderQuery, setOffenderQuery] = useState('');
    const [witnessQuery, setWitnessQuery] = useState('');
    const [offenderResults, setOffenderResults] = useState<SearchStudent[]>([]);
    const [witnessResults, setWitnessResults] = useState<SearchStudent[]>([]);
    const [selectedOffenders, setSelectedOffenders] = useState<SearchStudent[]>([]);
    const [selectedWitnesses, setSelectedWitnesses] = useState<SearchStudent[]>([]);

    const form = useForm({
        category_id: '',
        incident_title: '',
        description: '',
        incident_date: '',
        incident_time: '',
        location: '',
        victim_ids: [] as string[],
        offender_ids: [] as string[],
        witness_ids: [] as string[],
        evidence: [] as EvidenceItem[],
    });

    const selectedCategory = categories.find((category) => String(category.id) === String(form.data.category_id));

    useEffect(() => {
        const timer = window.setTimeout(async () => {
            if (offenderQuery.trim().length < 2) {
                setOffenderResults([]);
                return;
            }

            const response = await fetch(`${route('web.reports.student-search')}?q=${encodeURIComponent(offenderQuery.trim())}`);

            if (!response.ok) {
                setOffenderResults([]);
                return;
            }

            const data = await response.json();
            setOffenderResults(data.students ?? []);
        }, 300);

        return () => window.clearTimeout(timer);
    }, [offenderQuery]);

    useEffect(() => {
        const timer = window.setTimeout(async () => {
            if (witnessQuery.trim().length < 2) {
                setWitnessResults([]);
                return;
            }

            const response = await fetch(`${route('web.reports.student-search')}?q=${encodeURIComponent(witnessQuery.trim())}`);

            if (!response.ok) {
                setWitnessResults([]);
                return;
            }

            const data = await response.json();
            setWitnessResults(data.students ?? []);
        }, 300);

        return () => window.clearTimeout(timer);
    }, [witnessQuery]);

    const toggleVictim = (id: string) => {
        if (form.data.victim_ids.includes(id)) {
            form.setData('victim_ids', form.data.victim_ids.filter((item) => item !== id));
            return;
        }

        if (form.data.offender_ids.includes(id) || form.data.witness_ids.includes(id)) {
            setErrorMessage('A student cannot have multiple involvement roles in the same report.');
            return;
        }

        setErrorMessage('');
        form.setData('victim_ids', [...form.data.victim_ids, id]);
    };

    const addSearchStudent = (role: 'offender_ids' | 'witness_ids', student: SearchStudent) => {
        if (form.data.victim_ids.includes(student.id)) {
            setErrorMessage('A related victim cannot also be selected as an offender or witness.');
            return;
        }

        if (role === 'offender_ids' && form.data.witness_ids.includes(student.id)) {
            setErrorMessage('A student cannot be both an alleged offender and witness.');
            return;
        }

        if (role === 'witness_ids' && form.data.offender_ids.includes(student.id)) {
            setErrorMessage('A student cannot be both an alleged offender and witness.');
            return;
        }

        if (!form.data[role].includes(student.id)) {
            form.setData(role, [...form.data[role], student.id]);
        }

        if (role === 'offender_ids') {
            setSelectedOffenders((current) => current.some((item) => item.id === student.id) ? current : [...current, student]);
        } else {
            setSelectedWitnesses((current) => current.some((item) => item.id === student.id) ? current : [...current, student]);
        }

        setErrorMessage('');
        setOffenderQuery('');
        setWitnessQuery('');
    };

    const removeSearchStudent = (role: 'offender_ids' | 'witness_ids', id: string) => {
        form.setData(role, form.data[role].filter((item) => item !== id));

        if (role === 'offender_ids') {
            setSelectedOffenders((current) => current.filter((student) => student.id !== id));
        } else {
            setSelectedWitnesses((current) => current.filter((student) => student.id !== id));
        }
    };

    const addEvidence = (event: React.ChangeEvent<HTMLInputElement>) => {
        const files = Array.from(event.target.files ?? []);

        for (const file of files) {
            if (form.data.evidence.length >= MAX_FILES) {
                setErrorMessage(`You can upload up to ${MAX_FILES} evidence files.`);
                break;
            }

            if (!acceptedTypes.includes(file.type)) {
                setErrorMessage(`${file.name} has an unsupported file type.`);
                continue;
            }

            if (file.size > MAX_SIZE) {
                setErrorMessage(`${file.name} exceeds the 50 MB file size limit.`);
                continue;
            }

            form.setData('evidence', [...form.data.evidence, { file, caption: '' }]);
            setErrorMessage('');
        }

        event.target.value = '';
    };

    const removeEvidence = (index: number) => {
        form.setData('evidence', form.data.evidence.filter((_, itemIndex) => itemIndex !== index));
    };

    const updateCaption = (index: number, caption: string) => {
        form.setData('evidence', form.data.evidence.map((item, itemIndex) => itemIndex === index ? { ...item, caption } : item));
    };

    const reviewSubmit = (event: FormEvent) => {
        event.preventDefault();
        setErrorMessage('');

        if (!form.data.category_id) return setErrorMessage('Please select an incident category.');
        if (!form.data.incident_title.trim()) return setErrorMessage('Please provide an incident title.');
        if (form.data.description.trim().length < 20) return setErrorMessage('Please provide enough detail in the incident description.');
        if (!form.data.incident_date) return setErrorMessage('Please provide the incident date.');
        if (form.data.victim_ids.length === 0) return setErrorMessage('Please select at least one currently enrolled related student as the victim.');

        setConfirmOpen(true);
    };

    const submitReport = () => {
        form.post(route('web.reports.store'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setConfirmOpen(false),
        });
    };

    return (
        <TooltipProvider>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Report New Incident" />

                <div className="min-h-full bg-slate-50/70">
                    <form onSubmit={reviewSubmit} className="mx-auto w-full max-w-5xl space-y-6 px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                        {errorMessage && (
                            <div className="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                                {errorMessage}
                            </div>
                        )}

                        <section className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                            <div className="p-5 sm:p-7">
                                <div className="flex items-start gap-4">
                                    <div>
                                        <h1 className="mt-1 text-2xl font-bold tracking-tight text-slate-900">Report New Incident</h1>
                                        <p className="mt-2 text-sm leading-6 text-slate-500">Provide accurate and factual information about the incident. You may use English, Filipino, or both.</p>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <SectionCard title="Incident Information" icon={<FileText className="h-4 w-4 text-slate-500" />}>
                            <div className="grid grid-cols-1 gap-5 p-5 sm:p-6">
                                <div>
                                    <Label htmlFor="incident_title">Incident Title</Label>
                                    <Input id="incident_title" className="mt-2" value={form.data.incident_title} onChange={(e) => form.setData('incident_title', e.target.value)} placeholder="Briefly describe the incident" maxLength={150} />
                                </div>

                                <div>
                                    <Label htmlFor="category_id">Incident Category</Label>
                                    <select id="category_id" value={form.data.category_id} onChange={(e) => form.setData('category_id', e.target.value)} className="mt-2 h-10 w-full rounded-md border border-input bg-background px-3 text-sm">
                                        <option value="">Select a category</option>
                                        {categories.map((category) => <option key={category.id} value={category.id}>{category.category_name}</option>)}
                                    </select>
                                    {selectedCategory?.description && <p className="mt-1 text-xs text-slate-500">{selectedCategory.description}</p>}
                                </div>

                                <div>
                                    <Label htmlFor="description">What happened?</Label>
                                    <textarea id="description" rows={7} value={form.data.description} onChange={(e) => form.setData('description', e.target.value)} className="mt-2 w-full rounded-xl border border-input bg-background px-3 py-3 text-sm leading-6" placeholder="Describe what happened, who was involved, and any relevant context." maxLength={5000} />
                                    <p className="mt-1 text-right text-xs text-slate-400">{form.data.description.length}/5000</p>
                                </div>

                                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                                    <div>
                                        <Label htmlFor="incident_date">Incident Date</Label>
                                        <Input id="incident_date" type="date" className="mt-2" value={form.data.incident_date} onChange={(e) => form.setData('incident_date', e.target.value)} />
                                    </div>

                                    <div>
                                        <Label htmlFor="incident_time">Approximate Time <span className="text-xs font-normal text-slate-400">(optional)</span></Label>
                                        <Input id="incident_time" type="time" className="mt-2" value={form.data.incident_time} onChange={(e) => form.setData('incident_time', e.target.value)} />
                                    </div>
                                </div>

                                <div>
                                    <div className="flex items-center gap-2">
                                        <Label htmlFor="location">Location</Label>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <button type="button" className="text-slate-400"><Info className="h-4 w-4" /></button>
                                            </TooltipTrigger>
                                            <TooltipContent className="max-w-xs">
                                                Use an actual school room, facility, or digital platform. For cyberbullying, include the platform and specific group chat or conversation when appropriate.
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <Input
                                        id="location"
                                        className="mt-2"
                                        value={form.data.location}
                                        onChange={(e) => form.setData('location', e.target.value)}
                                        placeholder="e.g. Room 204 or Messenger – Grade 8 Section GC"
                                    />
                                </div>
                            </div>
                        </SectionCard>

                        <SectionCard title="Victim / Target Students" icon={<Users className="h-4 w-4 text-slate-500" />}>
                            <div className="p-5 sm:p-6">
                                <p className="mb-4 text-sm text-slate-500">Only your currently enrolled related students are available for selection.</p>

                                {relatedStudents.length > 0 ? (
                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                        {relatedStudents.map((student) => {
                                            const selected = form.data.victim_ids.includes(student.id);

                                            return (
                                                <button
                                                    key={student.id}
                                                    type="button"
                                                    onClick={() => toggleVictim(student.id)}
                                                    className={`rounded-2xl border p-4 text-left transition ${selected ? 'border-blue-500 bg-blue-50 ring-2 ring-blue-100' : 'border-slate-200 bg-white hover:bg-slate-50'}`}
                                                >
                                                    <div className="flex items-start gap-3">
                                                        <div className={`flex h-10 w-10 items-center justify-center rounded-xl ${selected ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-500'}`}>
                                                            {selected ? <CheckCircle2 className="h-5 w-5" /> : <UserRound className="h-5 w-5" />}
                                                        </div>

                                                        <div className="min-w-0">
                                                            <p className="truncate text-sm font-semibold text-slate-800">{fullName(student)}</p>
                                                            <p className="mt-1 text-xs font-medium text-blue-600">{student.student_number}</p>
                                                            <p className="mt-1 text-xs text-slate-500">
                                                                {student.grade_level ? `Grade ${student.grade_level}` : 'Grade unavailable'}
                                                                {student.section ? ` · ${student.section}` : ''}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </button>
                                            );
                                        })}
                                    </div>
                                ) : (
                                    <div className="rounded-2xl border border-amber-200 bg-amber-50 p-5">
                                        <div className="flex items-start gap-3">
                                            <AlertCircle className="mt-0.5 h-5 w-5 text-amber-600" />
                                            <div>
                                                <p className="text-sm font-semibold text-amber-900">No currently enrolled related students</p>
                                                <p className="mt-1 text-xs leading-5 text-amber-800">A student must have a current enrollment with status “Enrolled” before they can be selected as a victim.</p>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </SectionCard>

                        <StudentSearchSection
                            title="Alleged Offender(s)"
                            value={offenderQuery}
                            setValue={setOffenderQuery}
                            results={offenderResults}
                            selected={selectedOffenders}
                            addStudent={(student) => addSearchStudent('offender_ids', student)}
                            removeStudent={(id) => removeSearchStudent('offender_ids', id)}
                            emptyText="Leave blank if unknown."
                        />

                        <StudentSearchSection
                            title="Witness(es)"
                            value={witnessQuery}
                            setValue={setWitnessQuery}
                            results={witnessResults}
                            selected={selectedWitnesses}
                            addStudent={(student) => addSearchStudent('witness_ids', student)}
                            removeStudent={(id) => removeSearchStudent('witness_ids', id)}
                            emptyText="Leave blank if unknown."
                        />

                        <SectionCard title="Supporting Evidence" icon={<Upload className="h-4 w-4 text-slate-500" />}>
                            <div className="p-5 sm:p-6">
                                <div className="rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 p-6 text-center">
                                    <Upload className="mx-auto h-7 w-7 text-slate-400" />
                                    <p className="mt-3 text-sm font-semibold text-slate-700">Add image, video, or audio evidence</p>
                                    <p className="mt-1 text-xs text-slate-500">Up to 5 files, maximum 50 MB each.</p>

                                    <label className="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50">
                                        <Plus className="h-4 w-4" />
                                        Add Evidence
                                        <input
                                            type="file"
                                            className="hidden"
                                            multiple
                                            accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,audio/mpeg,audio/wav,audio/mp4,audio/x-m4a"
                                            onChange={addEvidence}
                                        />
                                    </label>
                                </div>

                                {form.data.evidence.length > 0 && (
                                    <div className="mt-5 space-y-3">
                                        {form.data.evidence.map((item, index) => (
                                            <div key={`${item.file.name}-${index}`} className="rounded-2xl border border-slate-200 bg-white p-4">
                                                <div className="flex items-start gap-3">
                                                    <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                                        {item.file.type.startsWith('image/') ? <FileImage className="h-5 w-5" /> : item.file.type.startsWith('video/') ? <FileVideo className="h-5 w-5" /> : <FileAudio className="h-5 w-5" />}
                                                    </div>

                                                    <div className="min-w-0 flex-1">
                                                        <p className="truncate text-sm font-semibold text-slate-800">{item.file.name}</p>
                                                        <p className="mt-1 text-xs text-slate-500">{item.file.type || 'Unknown'} · {(item.file.size / 1024 / 1024).toFixed(1)} MB</p>
                                                        <Input className="mt-3" value={item.caption} onChange={(e) => updateCaption(index, e.target.value)} placeholder="Optional caption or context" maxLength={500} />
                                                    </div>

                                                    <button type="button" onClick={() => removeEvidence(index)} className="rounded-lg p-2 text-slate-400 hover:bg-rose-50 hover:text-rose-600">
                                                        <Trash2 className="h-4 w-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </div>
                        </SectionCard>

                        <div className="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                            <Button type="button" variant="outline" onClick={() => router.visit(route('web.reports.index'))}>Cancel</Button>
                            <Button type="submit" disabled={form.processing || relatedStudents.length === 0} className="bg-blue-600 text-white hover:bg-blue-700">
                                <ShieldAlert className="h-4 w-4" />
                                Review & Submit
                            </Button>
                        </div>
                    </form>
                </div>

                <Modal
                    isOpen={confirmOpen}
                    onClose={() => !form.processing && setConfirmOpen(false)}
                    size="lg"
                    title="Confirm Incident Report"
                    description="Review the information before the report is submitted."
                    closeOnBackdropClick={!form.processing}
                    closeOnEsc={!form.processing}
                    footer={
                        <>
                            <Button type="button" variant="outline" disabled={form.processing} onClick={() => setConfirmOpen(false)}>Go Back</Button>
                            <Button type="button" disabled={form.processing} onClick={submitReport} className="bg-blue-600 text-white hover:bg-blue-700">
                                {form.processing ? 'Submitting...' : 'Confirm & Submit'}
                            </Button>
                        </>
                    }
                >
                    <div className="space-y-4">
                        <div className="rounded-2xl border border-blue-100 bg-red-50/70 p-4 text-sm font-bold leading-5 text-red-800">
                            Please ensure that all details in your report are accurate, factual, and verifiable. Submitting false, misleading, or exaggerated information
                            may result in administrative consequences and could affect the handling of the case. Your cooperation helps us maintain fairness and integrity in addressing bullying incidents.
                        </div>

                        <SummaryRow label="Category" value={selectedCategory?.category_name ?? 'Not selected'} />
                        <SummaryRow label="Incident" value={form.data.incident_title} />
                        <SummaryRow label="Date" value={form.data.incident_date} />
                        <SummaryRow label="Time" value={form.data.incident_time || 'Not specified'} />
                        <SummaryRow label="Location" value={form.data.location || 'Not specified'} />
                        <SummaryRow label="Victims" value={`${form.data.victim_ids.length} selected`} />
                        <SummaryRow label="Alleged Offenders" value={form.data.offender_ids.length ? `${form.data.offender_ids.length} selected` : 'Unknown / not specified'} />
                        <SummaryRow label="Witnesses" value={form.data.witness_ids.length ? `${form.data.witness_ids.length} selected` : 'Unknown / not specified'} />
                        <SummaryRow label="Evidence" value={`${form.data.evidence.length} file${form.data.evidence.length === 1 ? '' : 's'}`} />
                    </div>
                </Modal>
            </AppLayout>
        </TooltipProvider>
    );
}

function StudentSearchSection({ title, value, setValue, results, selected, addStudent, removeStudent, emptyText }: {
    title: string;
    value: string;
    setValue: (value: string) => void;
    results: SearchStudent[];
    selected: SearchStudent[];
    addStudent: (student: SearchStudent) => void;
    removeStudent: (id: string) => void;
    emptyText: string;
}) {
    return (
        <SectionCard title={title} icon={<UserRound className="h-4 w-4 text-slate-500" />}>
            <div className="p-5 sm:p-6">
                <div className="relative">
                    <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <Input value={value} onChange={(e) => setValue(e.target.value)} className="pl-9" placeholder="Search by student name or student number" />
                    {results.length > 0 && (
                        <div className="absolute z-30 mt-2 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg">
                            {results.map((student) => (
                                <button key={student.id} type="button" onClick={() => addStudent(student)} className="block w-full border-b border-slate-100 px-4 py-3 text-left last:border-b-0 hover:bg-slate-50">
                                    <p className="text-sm font-semibold text-slate-800">{fullName(student)}</p>
                                    <p className="mt-1 text-xs text-slate-500">
                                        {student.student_number}
                                        {student.grade_level ? ` · Grade ${student.grade_level}` : ''}
                                        {student.section ? ` · ${student.section}` : ''}
                                    </p>
                                </button>
                            ))}
                        </div>
                    )}
                </div>

                <p className="mt-2 text-xs text-slate-400">{emptyText}</p>

                {selected.length > 0 && (
                    <div className="mt-4 space-y-2">
                        {selected.map((student) => (
                            <div key={student.id} className="flex items-center justify-between rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                                <div>
                                    <p className="text-sm font-medium text-slate-700">{fullName(student)}</p>
                                    <p className="text-xs text-slate-400">{student.student_number}</p>
                                </div>
                                <button type="button" onClick={() => removeStudent(student.id)} className="rounded-lg p-1.5 text-slate-400 hover:bg-white hover:text-rose-600">
                                    <X className="h-4 w-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </SectionCard>
    );
}

function SummaryRow({ label, value }: { label: string; value: string }) {
    return (
        <div className="flex flex-col gap-1 border-b border-slate-100 pb-3 sm:flex-row sm:justify-between sm:gap-5">
            <span className="text-xs font-semibold uppercase tracking-wide text-slate-400">{label}</span>
            <span className="text-sm font-medium text-slate-700 sm:text-right">{value}</span>
        </div>
    );
}
