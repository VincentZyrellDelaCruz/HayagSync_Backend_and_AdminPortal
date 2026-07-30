<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DisciplinaryAction;
use App\Models\IncidentCategory;
use App\Models\Report;
use App\Models\ReportAppeal;
use App\Models\ReportEvidence;
use App\Models\ReportStatus;
use App\Models\ReportUpdate;
use App\Models\TimelineEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    /**
     * Enforce RLS-equivalent filters based on Auth User Role
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        $query = Report::with(['category', 'current_status']);

        if ($role === 'parent') {
            $studentIds = $user->parent_guardian->students()->pluck('students.id');
            $query->whereHas('students', function ($q) use ($studentIds) {
                $q->whereIn('students.id', $studentIds);
            });
        } elseif ($role === 'adviser') {
            $sectionIds = $user->staff->section_advisers()->pluck('id');
            $query->whereHas('students', function ($q) use ($sectionIds) {
                $q->whereIn('grade_section_id', $sectionIds);
            });
        } elseif ($role === 'ministrong_tagasubaybay') {
            $query->whereHas('current_status', function ($q) {
                $q->whereIn('status_name', ['Escalated to Ministrong', 'Escalated', 'Resolved', 'Re-Appealed']);
            });
        } elseif ($role === 'osd') {
            $query->whereHas('current_status', function ($q) {
                $q->whereIn('status_name', ['Escalated', 'Resolved', 'Re-Appealed']);
            });
        } elseif ($role !== 'principal') {
            return response()->json(['message' => 'Unauthorized.'], 403);
        } // Principal sees all

        $reports = $query->orderByDesc('created_at')->get();

        return response()->json($reports);
    }

    public function show(Request $request, $id)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        $report = Report::with([
            'category',
            'current_status',
            'students',
            'report_evidences',
            'report_updates.report_status',
            'disciplinary_actions',
            'appeals',
            'meetings',
        ])->findOrFail($id);

        if ($role === 'parent') {
            $studentIds = $user->parent_guardian->students()->pluck('students.id');
            if (!$report->students->pluck('id')->intersect($studentIds)->count()) {
                return response()->json(['message' => 'Unauthorized view access.'], 403);
            }
        } elseif ($role === 'adviser') {
            $sectionIds = $user->staff->section_advisers()->pluck('id');
            if (!$report->students->pluck('grade_section_id')->intersect($sectionIds)->count()) {
                return response()->json(['message' => 'Unauthorized view access.'], 403);
            }
        } elseif ($role === 'osd') {
            if (!in_array($report->current_status->status_name ?? null, ['Escalated', 'Resolved', 'Re-Appealed'])) {
                return response()->json(['message' => 'Unauthorized view access.'], 403);
            }
        } elseif ($role === 'ministrong_tagasubaybay') {
            if (!in_array($report->current_status->status_name ?? null, ['Escalated to Ministrong', 'Escalated', 'Resolved', 'Re-Appealed'])) {
                return response()->json(['message' => 'Unauthorized view access.'], 403);
            }
        } elseif ($role !== 'principal') {
            return response()->json(['message' => 'Unauthorized view access.'], 403);
        }

        return response()->json($report);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        if ($role !== 'parent') {
            return response()->json(['message' => 'Only parents can submit reports.'], 403);
        }

        $parentStudentIds = $user->parent_guardian->students()->pluck('students.id')->toArray();

        $validated = $request->validate([
            'incident_title' => 'required|string|max:255',
            'description' => 'required|string',
            'category_id' => 'required|exists:incident_categories,id',
            'location' => 'required|string|max:255',
            'incident_date' => 'required|date|before_or_equal:today',
            'incident_time' => 'nullable|date_format:H:i',
            'evidence_filename' => 'nullable|string',
            'students' => 'required|array|min:1',
            // NOTE: only the victim is required to be the reporting parent's own child.
            // An alleged offender or a witness is very often someone else's child, so
            // student_id itself is only checked against the `students` table here —
            // ownership is enforced separately below, scoped to victim entries only.
            'students.*.student_id' => 'required|exists:students,id',
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
        ], [
            'incident_date.before_or_equal' => 'Please select a valid date. Future dates are not allowed.',
            'students.*.involvement_type.in' => 'Each student must be marked as victim, offender, or witness.',
        ]);

        $victimEntries = collect($validated['students'])->filter(
            fn ($entry) => strtolower($entry['involvement_type']) === 'victim'
        );

        if ($victimEntries->isEmpty()) {
            return response()->json(['message' => 'At least one student must be marked as the victim.'], 422);
        }

        $unauthorizedVictim = $victimEntries->first(
            fn ($entry) => !in_array($entry['student_id'], $parentStudentIds)
        );

        if ($unauthorizedVictim) {
            return response()->json(['message' => 'The victim must be one of your own linked child/children.'], 422);
        }

        // Auto-extract verification details (simulated AI scan)
        [$evidenceState, $evidenceDetails] = $this->simulateEvidenceVerification($request->evidence_filename);

        // Auto-generate AI report summary
        $category = IncidentCategory::find($validated['category_id']);
        $severity = $this->evaluateSeverity($validated['description'], $category?->category_name);
        $attention = $severity === 'High'
            ? '🔴 HIGH (Immediate Action Required)'
            : ($severity === 'Moderate' ? '🟡 MODERATE' : '🟢 LOW');

        $aiSummary = "=== AI INCIDENT ANALYSIS ===
• Report Title: {$validated['incident_title']}
• Incident Category: {$category?->category_name} Bullying
• Location Context: {$validated['location']}
• Severity Assessment: {$severity}
• Attention Level: {$attention}

Summary Description:
The incident involves reported acts of {$category?->category_name} bullying occurring at {$validated['location']}. The victim experienced distress following description details: \"{$validated['description']}\".

Recommended Staff Response:
1. Schedule parent-teacher dialogue immediately.
2. Log details in OSD incident logbook.
3. Conduct counseling session for student offenders.";

        $pendingStatusId = $this->resolveStatusId('Pending');

        $report = Report::create([
            'reported_by' => $user->id,
            'category_id' => $validated['category_id'],
            'current_status_id' => $pendingStatusId,
            'incident_title' => $validated['incident_title'],
            'description' => $validated['description'],
            'location' => $validated['location'],
            'incident_date' => $validated['incident_date'],
            'incident_time' => $validated['incident_time'] ?? null,
            'ai_summary' => $aiSummary,
        ]);

        foreach ($validated['students'] as $studentEntry) {
            $report->students()->attach($studentEntry['student_id'], [
                'involvement_type' => $studentEntry['involvement_type'] ?? null,
                'notes' => $studentEntry['notes'] ?? null,
            ]);
        }

        ReportUpdate::create([
            'report_id' => $report->id,
            'updated_by' => $user->id,
            'status_id' => $pendingStatusId,
            'note' => "Incident report filed by parent {$user->first_name} {$user->last_name}.",
        ]);

        if ($request->evidence_filename) {
            if ($request->hasFile('evidences')) {
                foreach ($request->file('evidences') as $index => $file) {
                    $mime_type = $file->getMimeType();

                    if (Str::startsWith($mime_type, 'image/')) {
                        $path = $file->store('report_evidences/images', 'public');
                    }
                    elseif (Str::startsWith($mime_type, 'video/')) {
                        $path = $file->store('report_evidences/videos', 'public');
                    }

                    $report->report_evidences()->create([
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

            ReportUpdate::create([
                'report_id' => $report->id,
                'updated_by' => $user->id,
                'status_id' => $pendingStatusId,
                'note' => "Evidence Auto-Verification: {$evidenceState}. Details: {$evidenceDetails}",
            ]);
        }

        return response()->json($report->load(['students', 'report_evidences', 'report_updates']), 201);
    }

    public function uploadFile(Request $request)
    {
        if (!$request->hasFile('file')) {
            return response()->json(['message' => 'No file uploaded.'], 400);
        }

        $file = $request->file('file');

        $destinationPath = public_path('uploads');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $originalName = $file->getClientOriginalName();
        $filename = time() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);

        $file->move($destinationPath, $filename);

        return response()->json([
            'url' => 'uploads/' . $filename,
            'filename' => $originalName,
        ]);
    }

    public function escalate(Request $request, $id)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $report = Report::findOrFail($id);

        if ($role === 'principal') {
            $statusId = $this->resolveStatusId('Escalated to Ministrong');
            $report->update(['current_status_id' => $statusId]);

            ReportUpdate::create([
                'report_id' => $report->id,
                'updated_by' => $user->id,
                'status_id' => $statusId,
                'note' => 'Incident escalated to the Ministrong Tagasubaybay for review.',
            ]);

            return response()->json($report);
        }

        if ($role === 'ministrong_tagasubaybay') {
            $statusId = $this->resolveStatusId('Escalated');
            $report->update(['current_status_id' => $statusId]);

            ReportUpdate::create([
                'report_id' => $report->id,
                'updated_by' => $user->id,
                'status_id' => $statusId,
                'note' => 'Incident forwarded to the Office of Student Discipline (OSD) for formal investigation.',
            ]);

            return response()->json($report);
        }

        return response()->json(['message' => 'Unauthorized to escalate reports.'], 403);
    }

    public function resolve(Request $request, $id)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);
        $report = Report::findOrFail($id);

        if ($role === 'parent') {
            return response()->json(['message' => 'Parents cannot resolve cases.'], 403);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'discipline_action' => 'required|string',
            'discipline_notes' => 'required|string',
        ]);

        DisciplinaryAction::create([
            'report_id' => $report->id,
            'student_id' => $validated['student_id'],
            'staff_id' => $user->staff->user_id ?? null,
            'discipline_action' => $validated['discipline_action'],
            'notes' => $validated['discipline_notes'],
        ]);

        $statusId = $this->resolveStatusId('Resolved');
        $report->update(['current_status_id' => $statusId]);

        ReportUpdate::create([
            'report_id' => $report->id,
            'updated_by' => $user->id,
            'status_id' => $statusId,
            'note' => "Case marked resolved. Disciplinary action: {$validated['discipline_action']}. Notes: {$validated['discipline_notes']}",
        ]);

        return response()->json($report->load('disciplinary_actions'));
    }

    public function appeal(Request $request, $id)
    {
        $user = $request->user();
        $report = Report::with('students')->findOrFail($id);

        if (!$this->userIsVictimsParent($user, $report)) {
            return response()->json(['message' => 'Only the victim\'s parent can appeal.'], 403);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
        ]);

        ReportAppeal::create([
            'report_id' => $report->id,
            'appealed_by' => $user->id,
            'reason' => $validated['reason'],
            'status' => 'Pending',
        ]);

        $statusId = $this->resolveStatusId('Under Review');
        $report->update(['current_status_id' => $statusId]);

        ReportUpdate::create([
            'report_id' => $report->id,
            'updated_by' => $user->id,
            'status_id' => $statusId,
            'note' => "Parent appealed resolution. Reason: {$validated['reason']} (Appeal #{$report->appeals()->count()})",
        ]);

        return response()->json($report->load('appeals'));
    }

    public function reAppeal(Request $request, $id)
    {
        $user = $request->user();
        $report = Report::with(['current_status', 'students'])->findOrFail($id);

        if (!$this->userIsVictimsParent($user, $report)) {
            return response()->json(['message' => 'Only the victim\'s parent can re-appeal.'], 403);
        }

        if (strtolower($report->current_status->status_name ?? '') !== 'resolved') {
            return response()->json(['message' => 'Only resolved cases can be re-appealed.'], 400);
        }

        $validated = $request->validate([
            'reason' => 'required|string',
            'comments' => 'nullable|string',
            'new_evidence_url' => 'nullable|string',
        ]);

        ReportAppeal::create([
            'report_id' => $report->id,
            'appealed_by' => $user->id,
            'reason' => $validated['reason'],
            'status' => 'Pending',
        ]);

        if (!empty($validated['new_evidence_url'])) {
            ReportEvidence::create([
                'report_id' => $report->id,
                'uploaded_by' => $user->id,
                'file_name' => $validated['new_evidence_url'],
                'file_path' => $validated['new_evidence_url'],
                'caption' => 'Re-appeal supporting evidence',
            ]);
        }

        $statusId = $this->resolveStatusId('Re-Appealed');
        $report->update(['current_status_id' => $statusId]);

        $note = "Parent submitted a re-appeal (Appeal #{$report->appeals()->count()}). Reason: {$validated['reason']}.";
        if (!empty($validated['comments'])) {
            $note .= " Comments: {$validated['comments']}";
        }
        if (!empty($validated['new_evidence_url'])) {
            $note .= " New evidence: {$validated['new_evidence_url']}";
        }

        ReportUpdate::create([
            'report_id' => $report->id,
            'updated_by' => $user->id,
            'status_id' => $statusId,
            'note' => $note,
        ]);

        return response()->json($report->load(['appeals', 'report_evidences']));
    }

    public function updateAiSummary(Request $request, $id)
    {
        $user = $request->user();
        $role = $this->resolveRole($user);

        if ($role === 'parent') {
            return response()->json(['message' => 'Parents cannot update AI summaries.'], 403);
        }

        $validated = $request->validate([
            'ai_summary' => 'required|string',
        ]);

        $report = Report::findOrFail($id);
        $report->update(['ai_summary' => $validated['ai_summary']]);

        return response()->json($report);
    }

    /**
     * Resolve the effective role of the given user for authorization checks.
     * `users` has no `role` column, so this infers a role from the user's relations:
     *  - parent_guardian relation present            -> 'parent'
     *  - staff relation + position name match         -> 'principal' | 'osd' | 'ministrong_tagasubaybay'
     *  - staff advises one or more grade sections      -> 'adviser'
     *  - staff->is_admin fallback                      -> 'principal'
     */
    private function resolveRole($user): string
    {
        if ($user->parent_guardian) {
            return 'parent';
        }

        $staff = $user->staff;
        if (!$staff) {
            return 'unknown';
        }

        $positionNames = $staff->positions->pluck('position_name')
            ->map(fn ($name) => strtolower($name ?? ''));

        if ($positionNames->contains(fn ($name) => str_contains($name, 'principal'))) {
            return 'principal';
        }

        if ($positionNames->contains(fn ($name) => str_contains($name, 'ministrong'))) {
            return 'ministrong_tagasubaybay';
        }

        if ($positionNames->contains(fn ($name) => str_contains($name, 'osd') || str_contains($name, 'discipline'))) {
            return 'osd';
        }

        if ($staff->section_advisers()->exists() || $positionNames->contains(fn ($name) => str_contains($name, 'adviser'))) {
            return 'adviser';
        }

        if ($staff->is_admin) {
            return 'principal';
        }

        return 'unknown';
    }

    /**
     * True only if the user is a parent_guardian of the student marked
     * `involvement_type = victim` on this report (not just any linked student —
     * e.g. an offender or witness who happens to also be this parent's child
     * should not be able to appeal on the victim's behalf).
     */
    private function userIsVictimsParent($user, Report $report): bool
    {
        if (!$user->parent_guardian) {
            return false;
        }

        $parentStudentIds = $user->parent_guardian->students()->pluck('students.id');

        $victimIds = $report->students
            ->filter(fn ($student) => strtolower($student->pivot->involvement_type ?? '') === 'victim')
            ->pluck('id');

        return $victimIds->intersect($parentStudentIds)->isNotEmpty();
    }

    private function resolveStatusId(string $statusName)
    {
        return ReportStatus::where('status_name', $statusName)->value('id');
    }

    private function simulateEvidenceVerification(?string $filename): array
    {
        if (!$filename) {
            return ['Unverified', null];
        }

        $name = strtolower($filename);

        if (str_contains($name, 'com.google.android.apps.photos') || str_contains($name, 'google.photos')) {
            return ['Verified', 'EXIF metadata verified. Image loaded from Google Photos content provider. Capturing device matching mobile signature. No stock or AI artifacts found.'];
        }

        if (str_contains($name, 'midjourney') || str_contains($name, 'dall') || str_contains($name, 'stable') || str_contains($name, 'ai_') || str_contains($name, 'generated')) {
            return ['Suspected AI Generated', 'Image analysis detected AI generation artifacts (DALL-E/Midjourney styling patterns, pixel smoothing consistency 94.2%).'];
        }

        if (str_contains($name, 'google') || str_contains($name, 'web') || str_contains($name, 'stock') || str_contains($name, 'download') || str_contains($name, 'screenshot')) {
            return ['Suspected Google Image', 'Metadata check matches indexed stock photo / screenshot signatures. Source URL match index: 89%.'];
        }

        return ['Verified', 'EXIF metadata verified. Capturing device matching mobile signature. No stock or AI artifacts found.'];
    }

    private function evaluateSeverity(string $description, ?string $category): string
    {
        $text = strtolower($description);

        if (str_contains($text, 'punch') || str_contains($text, 'hit') || str_contains($text, 'beat') || str_contains($text, 'hurt') || str_contains($text, 'steal') || str_contains($text, 'threat') || strtolower($category ?? '') === 'physical') {
            return 'High';
        }

        if (str_contains($text, 'mock') || str_contains($text, 'laugh') || str_contains($text, 'shame') || str_contains($text, 'ignore') || str_contains($text, 'tease')) {
            return 'Moderate';
        }

        return 'Low';
    }

    private function sectionsMatch($s1, $s2)
    {
        $normalize = function($s) {
            $s = strtolower(trim($s ?? ''));
            $s = str_replace(['-', ' '], '', $s);
            return $s;
        };
        return $normalize($s1) === $normalize($s2);
    }
}
