<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateAiParentingSupportJob;
use App\Models\Incident;
use App\Models\IncidentStatus;
use App\Models\School;
use App\Services\GeminiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IncidentController extends Controller
{

    protected GeminiService $gemini;

    public function __construct(GeminiService $gemini)
    {
        $this->gemini = $gemini;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $incidents = Incident::with([
            'category',
            'latest_update',
        ])->where('reported_by', Auth::user()->id)->latest()->get();

        return response()->json($incidents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $req): JsonResponse
    {
        $user = Auth::user();

        $validated = $req->validate([
            'category_id' => 'required|exists:incident_categories,id',
            'incident_title' => 'required|string|max:255',
            'description' => 'required|string',
            'incident_datetime' => 'required|date_format:Y-m-d H:i:s',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'urgency_level' => 'nullable|string',

            'students' => 'nullable|array',
            'students.*.student_id' => 'required_with:students|exists:students,id',
            'students.*.involvement_type' => [
                'required_with:students',
                'string',
                Rule::in(['Victim', 'Offender', 'Witness']),
            ],
            'students.*.notes' => 'nullable|string',

            'evidences' => 'nullable|array',
            'evidences.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,avi|max:51200',
            'captions' => 'nullable|array',
            'captions.*' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $defaultStatus = IncidentStatus::where('status_name', 'Pending')->first();

            if (!$defaultStatus) {
                return response()->json([
                    'message' => 'Default incident status "Pending" not found.'
                ], 422);
            }

            // Create incident
            $incident = Incident::create([
                'school_id' => School::first()->id,
                'reported_by' => $user->id,
                'category_id' => $validated['category_id'],
                'current_status_id' => $defaultStatus->id,
                'incident_title' => $validated['incident_title'],
                'description' => $validated['description'],
                'incident_datetime' => $validated['incident_datetime'],
                'location' => $validated['location'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'urgency_level' => $validated['urgency_level'] ?? 1,
            ]);

            // Attach involved students to pivot table
            if (!empty($validated['students'])) {
                $pivotData = [];

                foreach ($validated['students'] as $student) {
                    $pivotData[$student['student_id']] = [
                        'involvement_type' => $student['involvement_type'],
                        'notes' => $student['notes'] ?? null,
                    ];
                }

                $incident->students()->attach($pivotData);
            }

            if ($req->hasFile('evidences')) {
                foreach ($req->file('evidences') as $index => $file) {
                    $mime_type = $file->getMimeType();

                    if (Str::startsWith($mime_type, 'image/')) {
                        $path = $file->store('incident_evidences/images', 'public');
                    }
                    elseif (Str::startsWith($mime_type, 'video/')) {
                        $path = $file->store('incident_evidences/videos', 'public');
                    }

                    $incident->incident_evidences()->create([
                        'uploaded_by' => $user->id,
                        'file_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'file_type' => $file->extension(),
                        'file_size' => $file->getSize(),
                        'mime_type' => $mime_type,
                        'caption' => $validated['captions'][$index] ?? null,
                        'hash_signature' => hash_file('sha256', $file->getRealPath()),
                    ]);
                }
            }

            /* $incident->incident_updates()->create([
                'updated_by' => $user->id,
                'status_id' => $defaultStatus->id,
                'note' => $validated['initial_update_note'] ?? 'Incident was created.',
            ]); */

            DB::commit();

            GenerateAiParentingSupportJob::dispatch($incident->id);

            $incident->load([
                'category',
                'current_status',
                'students',
                'incident_evidences',
                'ai_guidance',
            ]);

            return response()->json([
                'message' => 'Incident created successfully.',
                'data' => $incident,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create incident.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $incidents = Incident::with([
            'user.parent_guardian',
            'students',
            'latest_update.incident_status',
            'incident_evidences'
        ])->where('reported_by', Auth::user()->id)->findOrFail($id);

        return response()->json($incidents);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
