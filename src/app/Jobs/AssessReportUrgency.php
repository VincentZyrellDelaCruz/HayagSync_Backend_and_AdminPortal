<?php

namespace App\Jobs;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Services\NotificationService;
use App\Services\ReportAssignmentService;
use App\Services\ReportUrgencyService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AssessReportUrgency implements ShouldQueue
{
    use Dispatchable, Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(
        public string $reportId,
        public ?string $ipAddress = null,
    ) {}

    public function handle(
        ReportUrgencyService $urgencyService,
        ReportAssignmentService $assignmentService
    ): void {
        Log::info('AssessReportUrgency started.', [
            'report_id' => $this->reportId,
            'ip_address' => $this->ipAddress,
        ]);

        $report = Report::with([
            'category',
            'students.grade_sections',
            'current_status',
        ])->find($this->reportId);

        if (!$report) {
            Log::warning('AssessReportUrgency stopped because report was not found.', [
                'report_id' => $this->reportId,
            ]);

            return;
        }

        if ($report->urgent_safety_flag !== null) {
            Log::info('AssessReportUrgency skipped because urgency was already assessed.', [
                'report_id' => $report->id,
                'urgent_safety_flag' => $report->urgent_safety_flag,
            ]);

            return;
        }

        try {
            Log::info('Running report urgency assessment.', [
                'report_id' => $report->id,
            ]);

            $result = $urgencyService->assess($report);

            Log::info('Urgency assessment completed.', [
                'report_id' => $report->id,
                'urgent' => $result['urgent'],
                'source' => $result['source'],
                'traditional_score' => $result['traditional_score'] ?? null,
            ]);

            DB::transaction(function () use ($report, $result) {
                $updated = $report->update([
                    'urgent_safety_flag' => (bool) $result['urgent'],
                    'urgency_assessed_at' => now(),
                ]);

                if (!$updated) {
                    throw new \RuntimeException(
                        "Failed to update urgent_safety_flag for report {$report->id}."
                    );
                }
            });

            $report->refresh();

            if ($report->urgent_safety_flag === null) {
                throw new \RuntimeException(
                    "urgent_safety_flag remained NULL after update for report {$report->id}."
                );
            }

            /* try {
                ActivityLog::create([
                    'user_id' => $report->reported_by,
                    'action_type' => 'report_urgency_assessed',
                    'description' => sprintf(
                        'Urgency assessment completed for report %s. Result: %s. Source: %s.',
                        $report->report_code ?? $report->id,
                        $result['urgent'] ? 'URGENT' : 'NORMAL',
                        $result['source']
                    ),
                    'module' => 'reports',
                    'record_id' => $report->id,
                    'ip_address' => $this->ipAddress ?: '0.0.0.0',
                ]);
            } catch (Throwable $e) {
                Log::error('Failed to create urgency ActivityLog; urgency result remains saved.', [
                    'report_id' => $report->id,
                    'exception_message' => $e->getMessage(),
                ]);
            } */

            $report->refresh();

            Log::info('Urgency result saved successfully.', [
                'report_id' => $report->id,
                'urgent_safety_flag' => $report->urgent_safety_flag,
                'urgency_assessed_at' => $report->urgency_assessed_at?->toDateTimeString(),
            ]);

            $assignment = $assignmentService->assignAfterUrgency($report);

            if (!$assignment) {
                Log::warning('Report urgency succeeded but no assignment was created.', [
                    'report_id' => $report->id,
                    'urgent_safety_flag' => $report->urgent_safety_flag,
                ]);

                return;
            }

            $assignment->load('staff_assigned_to.user');

            Log::info('Report assignment created after urgency assessment.', [
                'report_id' => $report->id,
                'assignment_id' => $assignment->id,
                'assigned_to' => $assignment->assigned_to,
                'level' => $assignment->level,
            ]);

            $staff = $assignment->staff_assigned_to;

            if (!$staff?->user) {
                Log::warning('Assignment exists but assigned staff user could not be loaded.', [
                    'report_id' => $report->id,
                    'assignment_id' => $assignment->id,
                ]);

                return;
            }

            $urgent = (bool) $report->urgent_safety_flag;

            NotificationService::send(
                receiver: $staff->user,
                type: 'report_submitted',
                title: $urgent ? 'Urgent Incident Report' : 'New Incident Report',
                message: $urgent
                    ? sprintf(
                        'Incident report %s has been flagged for urgent safety handling and assigned to you.',
                        $report->report_code ?? $report->id
                    )
                    : sprintf(
                        'Incident report %s has been submitted and assigned for initial review.',
                        $report->report_code ?? $report->id
                    ),
                actionUrl: route('web.reports.show', $report->id),
                priority: $urgent ? 'high' : 'normal',
                data: [
                    'report_id' => (string) $report->id,
                    'report_code' => $report->report_code,
                    'urgent_safety' => $urgent,
                    'assignment_level' => (int) $assignment->level,
                ],
            );

            Log::info('AssessReportUrgency completed successfully.', [
                'report_id' => $report->id,
                'urgent_safety_flag' => $report->urgent_safety_flag,
                'assignment_level' => $assignment->level,
            ]);
        } catch (Throwable $e) {
            Log::error('AssessReportUrgency failed while processing report.', [
                'report_id' => $this->reportId,
                'ip_address' => $this->ipAddress,
                'exception_class' => $e::class,
                'exception_message' => $e->getMessage(),
                'exception_file' => $e->getFile(),
                'exception_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function failed(?Throwable $exception): void
    {
        Log::critical('AssessReportUrgency permanently failed after exhausting queue attempts.', [
            'report_id' => $this->reportId,
            'ip_address' => $this->ipAddress,
            'exception_class' => $exception ? $exception::class : null,
            'exception_message' => $exception?->getMessage(),
        ]);
    }
}
