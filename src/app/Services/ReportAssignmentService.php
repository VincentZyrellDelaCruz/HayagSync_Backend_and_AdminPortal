<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportAssignment;
use App\Models\Staff;

class ReportAssignmentService
{
    public function assignAfterUrgency(Report $report): ?ReportAssignment {
        $report->loadMissing(['current_status', 'students.grade_sections']);

        // DO NOT ASSIGN UNTIL URGENCY HAS BEEN ASSESSED
        if ( $report->urgent_safety_flag === null) return null;

        // PREVENT MULTIPLE ACTIVE ASSIGNMENTS CREATION
        $existing = $report->report_assignments()->whereNull('ended_at')->latest('assigned_at')->first();

        if ($existing) return $existing;

        if ($report->current_status?->status_name !== 'Pending') return null;

        // URGENT REPORTS BYPESS THE SECTION ADVISER (TEACHER)
        if ($report->urgent_safety_flag === true) {
            $ministrong = $this->findStaffForLevel(3);

            if (!$ministrong) return null;

            return $report->report_assignments()->create([
                'assigned_to' => $ministrong->getKey(),
                'assigned_by' => null,
                'level' => '3',
                'assigned_at' => now(),
                'ended_at' => null,
            ]);
        }

        foreach ($report->students as $student) {
            $involvement = strtolower((string) $student->pivot->involvement_type);

            if (!in_array($involvement,['victim', 'target',], true)) continue;

            foreach ($student->grade_sections as $gradeSection) {
                if ($gradeSection->pivot?->ended_at !== null || !$gradeSection->adviser) {
                    continue;
                }

                $teacher = Staff::find($gradeSection->adviser);

                if ($teacher && $this->staffLevel($teacher) === 1) {
                    return $report->report_assignments()->create([
                        'assigned_to' => $teacher->getKey(),
                        'assigned_by' => null,
                        'level' => '1',
                        'assigned_at' => now(),
                        'ended_at' => null,
                    ]);
                }
            }
        }

        return null;
    }

    private function findStaffForLevel(int $level): ?Staff {
        $position = match ($level) {
            1 => 'Teacher',
            2 => 'Principal',
            3 => 'Ministrong Tagasubaybay',
            4 => 'OSD Officer',
            default => null,
        };

        if (!$position) return null;

        return Staff::with('positions')->whereHas(
            'positions',
            function ($query) use (
                $position
            ) {
                $query->where(
                    'position_name',
                    $position
                );
            }
        )->orderBy('staff_number')->get()->first(
            fn ($staff) => $this->staffLevel($staff) === $level
        );
    }

    private function staffLevel(?Staff $staff): ?int {
        if (!$staff) return null;

        $staff->loadMissing('positions');

        $position = $staff->positions->sortByDesc('pivot.assigned_at')->first();

        return match ($position?->position_name) {
            'Teacher' => 1,
            'Principal' => 2,
            'Ministrong Tagasubaybay' => 3,
            'OSD Officer' => 4,
            default => null,
        };
    }
}
