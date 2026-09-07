<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessRosterImport;
use App\Models\DataImportBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rules\File;
use Inertia\Inertia;

class DataImportController extends Controller
{
    public function index(Request $request)
    {
        $selectedBatch = null;
        $selectedBatchResolved = false;

        $resolveSelectedBatch = function () use ($request, &$selectedBatch, &$selectedBatchResolved) {
            if ($selectedBatchResolved) return $selectedBatch;

            $selectedBatchResolved = true;

            if (!$request->filled('batch')) return $selectedBatch = null;

            $batch = DataImportBatch::findOrFail($request->query('batch'));

            abort_unless(
                (string) $batch->initiated_by === (string) Auth::id() ||
                    Auth::user()?->staff?->is_admin, 403
            );

            return $selectedBatch = $batch;
        };

        return Inertia::render(
            'Admin/DataImport/Index',
            [
                'batches' => fn () => DataImportBatch::with(
                    'initiatedBy:id,first_name,last_name'
                )->latest()->paginate(10)->withQueryString(),

                'activeBatch' => fn () =>
                    DataImportBatch::with('initiatedBy:id,first_name,last_name')->where(
                        'initiated_by',
                        Auth::id()
                    )->whereIn(
                        'status',
                        [
                            'queued',
                            'validating',
                            'processing',
                        ]
                    )->latest()->first(),

                'selectedBatch' => function () use ($resolveSelectedBatch) {
                    $batch = $resolveSelectedBatch();

                    if (!$batch) return null;

                    $batch->loadMissing('initiatedBy:id,first_name,last_name');

                    return $batch;
                },

                'changes' => function () use ($request, $resolveSelectedBatch) {
                    $batch = $resolveSelectedBatch();

                    if (!$batch) return null;

                    $changesPage = max(1, $request->integer('changes_page', 1));

                    $loadChanges = function () use ($batch) {
                        return $batch->rows()->orderBy('row_number')
                            ->paginate(
                                50,
                                [
                                    'id',
                                    'batch_id',
                                    'row_number',
                                    'identifier',
                                    'action',
                                    'errors',
                                    'before_data',
                                    'after_data',
                                ],
                                'changes_page'
                            )->withQueryString();
                    };

                    if (in_array($batch->status, [
                                'completed',
                                'failed',
                                'validation_failed',
                            ],
                            true
                        )
                    ) {
                        return Cache::remember(
                            "data-import:changes:{$batch->id}:{$changesPage}",
                            now()->addMinutes(5),
                            $loadChanges
                        );
                    }

                    return $loadChanges();
                },
            ]
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'import_type' => 'required|in:students,staff',
            'file' => [
                'required',
                File::types(['csv', 'xlsx'])->max('10mb'),
            ],
            'mode' => 'required|in:reference_only,full_roster',
        ]);

        $file = $request->file('file');

        $extension = strtolower($file->getClientOriginalExtension());

        $batch = DataImportBatch::create([
            'initiated_by' => $request->user()->id,
            'import_type' => $validated['import_type'],
            'file_name' => $file->getClientOriginalName(),
            'file_path' => '',
            'file_hash' => hash_file('sha256', $file->getRealPath()),
            'mode' => $validated['mode'],
            'status' => 'queued',
            'stage' => 'Queued for processing',
        ]);

        $path = $file->storeAs('imports', $batch->id . '.' . $extension);

        $batch->update([
            'file_path' => $path,
        ]);

        ProcessRosterImport::dispatch($batch->id); // REDIS QUEUING
            // ->onQueue('data-imports');

        return back()->with([
            'success' => 'The import file is now processing and validating',
            'import_batch_id' => $batch->id,
        ]);
    }

    public function status(Request $request,DataImportBatch $batch): JsonResponse {
        abort_unless((string) $batch->initiated_by === (string) Auth::id() ||
            Auth::user()?->staff?->is_admin, 403);

        $batch->load('initiatedBy:id,first_name,last_name');

        return response()->json([
            'id' => $batch->id,
            'initiated_by' => $batch->initiatedBy,
            'file_name' => $batch->file_name,
            'import_type' => $batch->import_type,
            'mode' => $batch->mode,
            'status' => $batch->status,
            'stage' => $batch->stage,
            'progress' => (int) $batch->progress,
            'total_rows' => (int) $batch->total_rows,
            'valid_rows' => (int) $batch->valid_rows,
            'invalid_rows' => (int) $batch->invalid_rows,
            'processed_rows' => (int) $batch->processed_rows,
            'created_count' => (int) $batch->created_count,
            'updated_count' => (int) $batch->updated_count,
            'deactivated_count' => (int) $batch->deactivated_count,
            'error_message' => $batch->error_message,
            'started_at' => $batch->started_at,
            'finished_at' => $batch->finished_at,
        ]);
    }
}
