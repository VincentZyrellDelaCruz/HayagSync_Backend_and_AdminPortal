import Pagination from '@/components/ui/pagination';
import AppLayout from '@/layouts/app-layout';
import {
    type BreadcrumbItem,
    type ImportBatchView,
    type ImportChange,
    type ImportProgressEvent,
    type Paginated,
    type SharedData
} from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Database, Eye, FileSpreadsheet, LoaderCircle, Upload, UsersRound, X, XCircle } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { useEcho } from '@laravel/echo-react';

declare function route(name: string, params?: Record<string, unknown> | number | string): string;

interface FlashProps {
    import_batch_id?: string | null;
}

interface PageProps extends SharedData {
    flash?: FlashProps;
}

interface Props {
    batches: Paginated<ImportBatchView>;
    activeBatch?: ImportBatchView | null;
    selectedBatch?: ImportBatchView | null;
    changes?: Paginated<ImportChange> | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Data Import and Update', href: '/admin/imports' }];

const terminalStatuses = ['completed', 'failed', 'validation_failed'];

const statusClasses = (status: string) => {
    switch (status) {
        case 'completed':
            return 'bg-emerald-100 text-emerald-700 ring-1 ring-emerald-200';
        case 'failed':
        case 'validation_failed':
            return 'bg-red-100 text-red-700 ring-1 ring-red-200';
        case 'processing':
            return 'bg-blue-100 text-blue-700 ring-1 ring-blue-200';
        case 'validating':
            return 'bg-amber-100 text-amber-700 ring-1 ring-amber-200';
        case 'queued':
            return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
        default:
            return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
    }
};

const statusLabel = (status: string) => status.replace(/_/g, ' ');

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'object') return JSON.stringify(value);
    return String(value);
};

const flattenData = (data?: Record<string, unknown> | null, prefix = ''): Array<{ key: string; value: unknown }> => {
    if (!data) return [];

    return Object.entries(data).flatMap(([key, value]) => {
        const path = prefix ? `${prefix}.${key}` : key;

        if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
            return flattenData(value as Record<string, unknown>, path);
        }

        return [{ key: path, value }];
    });
};

const getChangedFields = (before?: Record<string, unknown> | null, after?: Record<string, unknown> | null) => {
    const beforeFields = flattenData(before);
    const afterFields = flattenData(after);

    const values = new Map<string, { before: unknown; after: unknown }>();

    beforeFields.forEach((item) => {
        values.set(item.key, { before: item.value, after: undefined });
    });

    afterFields.forEach((item) => {
        const existing = values.get(item.key);
        values.set(item.key, { before: existing?.before, after: item.value });
    });

    return Array.from(values.entries())
        .map(([key, value]) => ({ key, before: value.before, after: value.after }))
        .filter((item) => JSON.stringify(item.before) !== JSON.stringify(item.after));
};

const formatFieldName = (value: string) =>
    value
        .split('.')
        .map((part) => part.replace(/_/g, ' ').replace(/\b\w/g, (letter) => letter.toUpperCase()))
        .join(' / ');

export default function DataImportIndex({ batches, activeBatch, selectedBatch, changes }: Props) {
    const { flash, auth } = usePage<PageProps>().props;

    const [history, setHistory] = useState<ImportBatchView[]>(batches.data);
    const [batchStatus, setBatchStatus] = useState<ImportBatchView | null>(activeBatch ?? null);
    const [importType, setImportType] = useState<'students' | 'staff'>('students');
    const [activeBatchId, setActiveBatchId] = useState<string | null>(flash?.import_batch_id ?? activeBatch?.id ?? null);

    const { data, setData, post, processing, errors, reset } = useForm<{
        import_type: 'students' | 'staff';
        file: File | null;
        mode: 'reference_only' | 'full_roster';
    }>({
        import_type: 'students',
        file: null,
        mode: 'full_roster',
    });

    useEffect(() => {
        setHistory(batches.data);
    }, [batches.data]);

    useEffect(() => {
        if (!activeBatch) return;

        setBatchStatus(activeBatch);
        setActiveBatchId(activeBatch.id);
    }, [activeBatch?.id]);

    const mergeBatch = (nextBatch: ImportBatchView) => {
        setBatchStatus((current) => {
            if (current && current.id === nextBatch.id) {
                const currentTerminal = terminalStatuses.includes(current.status);
                const nextTerminal = terminalStatuses.includes(nextBatch.status);

                if (currentTerminal && !nextTerminal) return current;
                if (!nextTerminal && nextBatch.progress < current.progress) return current;
            }

            return nextBatch;
        });

        setHistory((current) => {
            const exists = current.some((batch) => batch.id === nextBatch.id);

            if (!exists) return [nextBatch, ...current];

            return current.map((batch) => (batch.id === nextBatch.id ? { ...batch, ...nextBatch } : batch));
        });

        if (terminalStatuses.includes(nextBatch.status)) {
            setActiveBatchId((currentId) => (currentId === nextBatch.id ? null : currentId));
        }
    };

    useEcho(`user.${auth.user.id}`, 'ImportProgressUpdated', (event: ImportProgressEvent) => {
        mergeBatch(event.batch);
    });

    useEffect(() => {
        if (!activeBatchId) return;

        let mounted = true;

        const fetchStatus = async () => {
            try {
                const response = await fetch(route('web.admin.imports.status', activeBatchId), {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });

                if (!response.ok || !mounted) return;

                const result = (await response.json()) as ImportBatchView;

                mergeBatch(result);
            } catch {
                //
            }
        };

        void fetchStatus();

        const timer = window.setInterval(fetchStatus, 5000);

        return () => {
            mounted = false;
            window.clearInterval(timer);
        };
    }, [activeBatchId]);

    const submit = (event: React.FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        post(route('web.admin.imports.store'), {
            forceFormData: true,
            onSuccess: (page) => {
                const id = (page.props as unknown as PageProps)?.flash?.import_batch_id ?? null;

                setActiveBatchId(id);
                setBatchStatus(null);
                reset('file');
            },
        });
    };

    const handleTypeChange = (value: 'students' | 'staff') => {
        setImportType(value);
        setData('import_type', value);
        reset('file');
    };

    const isRunning = batchStatus !== null && !terminalStatuses.includes(batchStatus.status);
    const selectedChanges = changes?.data ?? [];

    const selectedChangeRows = useMemo(() => selectedChanges.map((change) => ({
                change,
                fields: getChangedFields(
                    change.before_data,
                    change.after_data
                ),
            })),
        [selectedChanges]
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Data Import and Update" />

            <div className="min-h-full bg-slate-50/70">
                <div className="mx-auto w-full max-w-7xl px-4 py-5 sm:px-6 sm:py-7 lg:px-8 lg:py-8">
                    <div className="mb-6">
                        <div className="flex items-start gap-3">
                            <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                                <Database className="h-5 w-5" />
                            </div>

                            <div>
                                <h1 className="text-2xl font-bold tracking-tight text-slate-900">Data Import and Update</h1>
                                <p className="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                                    Securely synchronize authorized school roster data into this system.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-12">
                        <section className="lg:col-span-7">
                            <form onSubmit={submit} className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div className="border-b border-slate-200 px-5 py-4">
                                    <div className="flex rounded-xl bg-slate-100 p-1">
                                        <button
                                            type="button"
                                            onClick={() => handleTypeChange('students')}
                                            className={`flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                                                importType === 'students' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                            }`}
                                        >
                                            <UsersRound className="h-4 w-4" />
                                            Student Records
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => handleTypeChange('staff')}
                                            className={`flex flex-1 items-center justify-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium transition ${
                                                importType === 'staff' ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-700'
                                            }`}
                                        >
                                            <Database className="h-4 w-4" />
                                            Staff Records
                                        </button>
                                    </div>
                                </div>

                                <div className="space-y-5 p-5">
                                    <div>
                                        <label className="mb-2 block text-sm font-semibold text-slate-700">Import Mode</label>

                                        <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                            <label className="cursor-pointer rounded-xl border border-slate-200 p-3 transition has-checked:border-blue-500 has-checked:bg-blue-50">
                                                <input
                                                    type="radio"
                                                    name="mode"
                                                    value="reference_only"
                                                    checked={data.mode === 'reference_only'}
                                                    onChange={() => setData('mode', 'reference_only')}
                                                    className="sr-only"
                                                />

                                                <p className="text-sm font-semibold text-slate-800">Reference Only</p>
                                                <p className="mt-1 text-xs leading-5 text-slate-500">
                                                    Create/update rows found in the file without ending absent records.
                                                </p>
                                            </label>

                                            <label className="cursor-pointer rounded-xl border border-slate-200 p-3 transition has-checked:border-blue-500 has-checked:bg-blue-50">
                                                <input
                                                    type="radio"
                                                    name="mode"
                                                    value="full_roster"
                                                    checked={data.mode === 'full_roster'}
                                                    onChange={() => setData('mode', 'full_roster')}
                                                    className="sr-only"
                                                />

                                                <p className="text-sm font-semibold text-slate-800">Full Authoritative Roster</p>
                                                <p className="mt-1 text-xs leading-5 text-slate-500">
                                                    Missing active records can be marked no longer active.
                                                </p>
                                            </label>
                                        </div>
                                    </div>

                                    <div>
                                        <label className="mb-2 block text-sm font-semibold text-slate-700">Source File</label>

                                        <label className="flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-slate-50/50 px-5 text-center transition hover:border-blue-300 hover:bg-blue-50/30">
                                            <input
                                                type="file"
                                                accept=".csv,.xlsx"
                                                className="sr-only"
                                                onChange={(event) => setData('file', event.target.files?.[0] ?? null)}
                                            />

                                            <div className="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm">
                                                {data.file ? <FileSpreadsheet className="h-6 w-6" /> : <Upload className="h-6 w-6" />}
                                            </div>

                                            <p className="text-sm font-semibold text-slate-700">{data.file ? data.file.name : 'Choose a CSV or XLSX file'}</p>
                                            <p className="mt-1 text-xs text-slate-400">Maximum 10 MB</p>
                                        </label>

                                        {errors.file && <p className="mt-2 text-xs text-red-600">{errors.file}</p>}
                                    </div>

                                    {data.mode === 'full_roster' && (
                                        <div className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
                                            <p className="text-xs leading-5 text-amber-800">
                                                Only enable Full Authoritative Roster when the uploaded file represents the complete current roster from the registrar or authorized HR source.
                                            </p>
                                        </div>
                                    )}

                                    <button
                                        type="submit"
                                        disabled={processing || !data.file || !!isRunning}
                                        className="inline-flex h-11 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                    >
                                        {processing ? (
                                            <>
                                                <LoaderCircle className="h-4 w-4 animate-spin" />
                                                Uploading...
                                            </>
                                        ) : (
                                            <>
                                                <Upload className="h-4 w-4" />
                                                Upload & Start ETL
                                            </>
                                        )}
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section className="lg:col-span-5">
                            <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                                <div className="border-b border-slate-200 px-5 py-4">
                                    <h2 className="text-sm font-semibold text-slate-800">ETL Processing</h2>
                                </div>

                                <div className="p-5">
                                    {batchStatus ? (
                                        <div className="space-y-5">
                                            <div className="flex items-start justify-between gap-3">
                                                <div className="min-w-0">
                                                    <p className="truncate text-sm font-semibold text-slate-800">{batchStatus.file_name}</p>
                                                    <p className="mt-1 text-xs text-slate-500">{batchStatus.stage}</p>
                                                </div>

                                                <span className={`shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${statusClasses(batchStatus.status)}`}>
                                                    {statusLabel(batchStatus.status)}
                                                </span>
                                            </div>

                                            <div className="flex items-center gap-3">
                                                {batchStatus.status === 'completed' ? (
                                                    <CheckCircle2 className="h-6 w-6 shrink-0 text-emerald-500" />
                                                ) : batchStatus.status === 'failed' || batchStatus.status === 'validation_failed' ? (
                                                    <XCircle className="h-6 w-6 shrink-0 text-red-500" />
                                                ) : (
                                                    <LoaderCircle className="h-6 w-6 shrink-0 animate-spin text-blue-600" />
                                                )}

                                                <div className="min-w-0 flex-1">
                                                    <div className="mb-2 flex items-center justify-between text-xs">
                                                        <span className="font-medium text-slate-500">Progress</span>
                                                        <span className="font-semibold text-slate-700">{batchStatus.progress}%</span>
                                                    </div>

                                                    <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className="h-full rounded-full bg-blue-600 transition-all duration-500"
                                                            style={{ width: `${Math.min(Math.max(batchStatus.progress, 0), 100)}%` }}
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-2">
                                                <Stat label="Valid" value={batchStatus.valid_rows} />
                                                <Stat label="Invalid" value={batchStatus.invalid_rows} />
                                                <Stat label="Created" value={batchStatus.created_count} />
                                                <Stat label="Updated" value={batchStatus.updated_count} />
                                                <Stat label="Ended / Inactive" value={batchStatus.deactivated_count} />
                                                <Stat label="Rows" value={batchStatus.total_rows} />
                                            </div>

                                            {batchStatus.error_message && (
                                                <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs leading-5 text-red-700">
                                                    {batchStatus.error_message}
                                                </div>
                                            )}
                                        </div>
                                    ) : (
                                        <div className="py-12 text-center">
                                            <div className="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-slate-100 text-slate-400">
                                                <Database className="h-5 w-5" />
                                            </div>

                                            <p className="text-sm font-medium text-slate-700">No active ETL process</p>
                                            <p className="mt-1 text-xs leading-5 text-slate-400">Upload an authorized roster to begin synchronization.</p>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </section>
                    </div>

                    <div className="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div className="border-b border-slate-200 px-5 py-4">
                            <div className="flex items-center justify-between gap-3">
                                <h2 className="text-sm font-semibold text-slate-800">Import History</h2>
                                <p className="text-xs text-slate-400">{batches.total} imports</p>
                            </div>
                        </div>

                        <div className="hidden overflow-x-auto md:block">
                            <table className="min-w-full divide-y divide-slate-200">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">File</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Changes</th>
                                        <th className="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Started By</th>
                                        <th className="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Details</th>
                                    </tr>
                                </thead>

                                <tbody className="divide-y divide-slate-100">
                                    {history.length > 0 ? (
                                        history.map((batch) => (
                                            <tr key={batch.id} className="transition hover:bg-slate-50">
                                                <td className="px-5 py-4 text-sm font-medium text-slate-700">
                                                    {batch.import_type === 'students' ? 'Students' : 'Staff'}
                                                </td>

                                                <td className="px-5 py-4">
                                                    <p className="max-w-xs truncate text-sm text-slate-700">{batch.file_name}</p>
                                                    <p className="mt-0.5 text-xs text-slate-400">{batch.mode === 'full_roster' ? 'Full roster' : 'Reference only'}</p>
                                                </td>

                                                <td className="px-5 py-4">
                                                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold capitalize ${statusClasses(batch.status)}`}>
                                                        {statusLabel(batch.status)}
                                                    </span>
                                                </td>

                                                <td className="px-5 py-4 text-xs text-slate-500">
                                                    +{batch.created_count} created · {batch.updated_count} updated · {batch.deactivated_count} inactive
                                                </td>

                                                <td className="px-5 py-4 text-sm text-slate-600">
                                                    {batch.initiated_by ? `${batch.initiated_by.last_name}, ${batch.initiated_by.first_name}` : 'Unknown'}
                                                </td>

                                                <td className="px-5 py-4 text-right">
                                                    <Link
                                                        href={route('web.admin.imports.index', { batch: batch.id })}
                                                        only={['selectedBatch', 'changes']}
                                                        preserveState
                                                        preserveScroll
                                                        className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200 hover:text-slate-900"
                                                    >
                                                        <Eye className="h-3.5 w-3.5" />
                                                        View Changes
                                                    </Link>
                                                </td>
                                            </tr>
                                        ))
                                    ) : (
                                        <tr>
                                            <td colSpan={6} className="px-5 py-12 text-center text-sm text-slate-400">No import history yet.</td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </div>

                        <div className="divide-y divide-slate-100 md:hidden">
                            {history.length > 0 ? (
                                history.map((batch) => (
                                    <div key={batch.id} className="p-4">
                                        <div className="flex items-start justify-between gap-3">
                                            <div className="min-w-0">
                                                <p className="truncate text-sm font-semibold text-slate-800">{batch.file_name}</p>
                                                <p className="mt-1 text-xs text-slate-500">{batch.import_type === 'students' ? 'Student Records' : 'Staff Records'}</p>
                                            </div>

                                            <span className={`shrink-0 rounded-full px-2 py-1 text-[11px] font-semibold capitalize ${statusClasses(batch.status)}`}>
                                                {statusLabel(batch.status)}
                                            </span>
                                        </div>

                                        <div className="mt-3 grid grid-cols-3 gap-2 text-xs">
                                            <Stat label="Created" value={batch.created_count} />
                                            <Stat label="Updated" value={batch.updated_count} />
                                            <Stat label="Inactive" value={batch.deactivated_count} />
                                        </div>

                                        <div className="mt-3 flex items-center justify-between gap-3">
                                            <p className="truncate text-xs text-slate-400">
                                                {batch.initiated_by ? `${batch.initiated_by.last_name}, ${batch.initiated_by.first_name}` : 'Unknown'}
                                            </p>

                                            <Link
                                                href={route('web.admin.imports.index', { batch: batch.id })}
                                                only={['selectedBatch', 'changes']}
                                                preserveState
                                                preserveScroll
                                                className="inline-flex items-center gap-1.5 rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-medium text-slate-700 transition hover:bg-slate-200 hover:text-slate-900"
                                            >
                                                <Eye className="h-3.5 w-3.5" />
                                                Changes
                                            </Link>
                                        </div>
                                    </div>
                                ))
                            ) : (
                                <div className="px-5 py-12 text-center text-sm text-slate-400">No import history yet.</div>
                            )}
                        </div>

                        {batches.links.length > 3 && (
                            <div className="overflow-x-auto border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                                <Pagination links={batches.links} meta={{ from: batches.from, to: batches.to, total: batches.total }} />
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {selectedBatch && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/50 p-4 backdrop-blur-sm">
                    <div className="flex max-h-[90vh] w-full max-w-6xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
                        <div className="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                            <div className="min-w-0">
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="truncate text-base font-semibold text-slate-900">Import Changes</h2>
                                    <span className={`rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize ${statusClasses(selectedBatch.status)}`}>
                                        {statusLabel(selectedBatch.status)}
                                    </span>
                                </div>

                                <p className="mt-1 truncate text-xs text-slate-500">{selectedBatch.file_name}</p>
                            </div>

                            <Link
                                href={route('web.admin.imports.index')}
                                only={['selectedBatch', 'changes']}
                                preserveState
                                preserveScroll
                                className="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                aria-label="Close"
                            >
                                <X className="h-5 w-5" />
                            </Link>
                        </div>

                        <div className="overflow-y-auto">
                            <div className="border-b border-slate-200 bg-slate-50 px-5 py-4">
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-5">
                                    <Stat label="Rows" value={selectedBatch.total_rows} />
                                    <Stat label="Valid" value={selectedBatch.valid_rows} />
                                    <Stat label="Created" value={selectedBatch.created_count} />
                                    <Stat label="Updated" value={selectedBatch.updated_count} />
                                    <Stat label="Inactive" value={selectedBatch.deactivated_count} />
                                </div>
                            </div>

                            {selectedChangeRows.length > 0 ? (
                                <div className="divide-y divide-slate-200">
                                    {selectedChangeRows.map(({ change, fields }) => {
                                        return (
                                            <div key={change.id} className="p-5">
                                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                    <div>
                                                        <div className="flex flex-wrap items-center gap-2">
                                                            <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">Row {change.row_number}</span>
                                                            <span className="rounded-lg bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">{change.identifier}</span>
                                                            <span className="rounded-lg bg-slate-100 px-2.5 py-1 text-xs font-medium capitalize text-slate-600">{change.action ?? 'unchanged'}</span>
                                                        </div>

                                                        <p className="mt-2 text-xs text-slate-400">{selectedBatch.import_type}</p>
                                                    </div>
                                                </div>

                                                {fields.length > 0 ? (
                                                    <div className="mt-4 overflow-x-auto rounded-xl border border-slate-200">
                                                        <table className="min-w-full divide-y divide-slate-200">
                                                            <thead className="bg-slate-50">
                                                                <tr>
                                                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Field</th>
                                                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Before</th>
                                                                    <th className="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">After</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody className="divide-y divide-slate-100">
                                                                {fields.map((field) => (
                                                                    <tr key={field.key}>
                                                                        <td className="px-4 py-3 text-xs font-medium text-slate-700">{formatFieldName(field.key)}</td>
                                                                        <td className="max-w-xs px-4 py-3 text-xs wrap-break-word text-red-600">{formatValue(field.before)}</td>
                                                                        <td className="max-w-xs px-4 py-3 text-xs wrap-break-word text-emerald-600">{formatValue(field.after)}</td>
                                                                    </tr>
                                                                ))}
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                ) : (
                                                    <div className="mt-4 rounded-xl bg-slate-50 px-4 py-3 text-xs text-slate-500">No field-level changes were recorded.</div>
                                                )}

                                                {change.errors && change.errors.length > 0 && (
                                                    <div className="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3">
                                                        <p className="text-xs font-semibold text-red-700">Validation Issues</p>

                                                        <div className="mt-2 space-y-1">
                                                            {change.errors.map((error, index) => (
                                                                <p key={index} className="text-xs text-red-600">{error}</p>
                                                            ))}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                        );
                                    })}
                                </div>
                            ) : (
                                <div className="px-5 py-16 text-center">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <Database className="h-5 w-5" />
                                    </div>

                                    <p className="text-sm font-medium text-slate-700">No recorded changes</p>
                                    <p className="mt-1 text-xs text-slate-500">This import did not produce any staged record changes.</p>
                                </div>
                            )}
                        </div>

                        {changes && changes.links.length > 3 && (
                            <div className="border-t border-slate-200 bg-white px-4 py-4 sm:px-5">
                                <Pagination links={changes.links} meta={{ from: changes.from, to: changes.to, total: changes.total }} />
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AppLayout>
    );
}

function Stat({ label, value }: { label: string; value: number }) {
    return (
        <div className="rounded-xl bg-slate-50 px-3 py-2.5">
            <p className="text-[10px] font-medium uppercase tracking-wide text-slate-400">{label}</p>
            <p className="mt-0.5 text-sm font-bold text-slate-700">{value}</p>
        </div>
    );
}
