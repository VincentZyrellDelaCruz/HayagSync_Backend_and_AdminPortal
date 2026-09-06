<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function addIndex(string $table, string|array $columns, string $name): void {
        if (Schema::hasTable($table) && !Schema::hasIndex($table, $name)) {
            Schema::table($table, function (Blueprint $tableBlueprint) use ($columns, $name) {
                $tableBlueprint->index($columns, $name);
            });
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // GRADE SECTIONS
        $this->addIndex(
            'grade_sections',
            ['adviser', 'grade_level', 'section'],
            'grade_sections_adviser_grade_section_index'
        );

        $this->addIndex(
            'grade_sections',
            ['grade_level', 'section'],
            'grade_sections_grade_section_index'
        );


        // SCHOOL YEARS
        $this->addIndex(
            'school_years',
            'is_active',
            'school_years_is_active_index'
        );


        // ENROLLMENTS
        $this->addIndex(
            'enrollments',
            ['student_id', 'ended_at', 'enrolled_at'],
            'enrollments_student_current_latest_index'
        );

        $this->addIndex(
            'enrollments',
            ['grade_section_id', 'ended_at', 'student_id'],
            'enrollments_section_current_student_index'
        );


        // POSITIONS
        $this->addIndex(
            'positions',
            'position_name',
            'positions_position_name_index'
        );


        // STAFF POSITION PIVOT
        $this->addIndex(
            'staff_position',
            ['staff_id', 'assigned_at'],
            'staff_position_staff_assigned_index'
        );

        $this->addIndex(
            'staff_position',
            ['position_id', 'staff_id'],
            'staff_position_position_staff_index'
        );


        // PARENT/GUARDIAN - STUDENT PIVOT
        $this->addIndex(
            'student_parent_guardian',
            ['parent_id', 'student_id'],
            'student_parent_guardian_parent_student_index'
        );

        $this->addIndex(
            'student_parent_guardian',
            ['student_id', 'parent_id'],
            'student_parent_guardian_student_parent_index'
        );


        // INCIDENT CATEGORIES
        $this->addIndex(
            'incident_categories',
            ['is_active', 'category_name'],
            'incident_categories_active_name_index'
        );


        // REPORTS
        $this->addIndex(
            'reports',
            'created_at',
            'reports_created_at_index'
        );

        $this->addIndex(
            'reports',
            'updated_at',
            'reports_updated_at_index'
        );

        $this->addIndex(
            'reports',
            'reported_by',
            'reports_reported_by_index'
        );

        $this->addIndex(
            'reports',
            ['current_status_id', 'created_at'],
            'reports_status_created_index'
        );

        $this->addIndex(
            'reports',
            ['category_id', 'created_at'],
            'reports_category_created_index'
        );


        // REPORT - STUDENT PIVOT
        $this->addIndex(
            'report_student',
            ['report_id', 'involvement_type', 'student_id'],
            'report_student_report_involvement_student_index'
        );

        $this->addIndex(
            'report_student',
            ['student_id', 'involvement_type', 'report_id'],
            'report_student_student_involvement_report_index'
        );


        // REPORT EVIDENCE
        $this->addIndex(
            'report_evidence',
            ['report_id', 'created_at'],
            'report_evidence_report_created_index'
        );

        $this->addIndex(
            'report_evidence',
            'uploaded_by',
            'report_evidence_uploaded_by_index'
        );


        // REPORT UPDATES
        $this->addIndex(
            'report_updates',
            ['report_id', 'created_at', 'updated_at'],
            'report_updates_report_created_updated_index'
        );

        $this->addIndex(
            'report_updates',
            'status_id',
            'report_updates_status_index'
        );

        $this->addIndex(
            'report_updates',
            'updated_by',
            'report_updates_updated_by_index'
        );


        // REPORT ASSIGNMENTS
        $this->addIndex(
            'report_assignments',
            ['report_id', 'assigned_at', 'created_at'],
            'report_assignments_report_latest_index'
        );

        $this->addIndex(
            'report_assignments',
            ['assigned_to', 'ended_at', 'report_id'],
            'report_assignments_assignee_active_index'
        );

        $this->addIndex(
            'report_assignments',
            ['assigned_by', 'report_id'],
            'report_assignments_assigner_report_index'
        );


        // DISCIPLINARY ACTIONS
        $this->addIndex(
            'disciplinary_actions',
            ['student_id', 'created_at'],
            'disciplinary_actions_student_created_index'
        );

        $this->addIndex(
            'disciplinary_actions',
            ['report_id', 'created_at'],
            'disciplinary_actions_report_created_index'
        );

        $this->addIndex(
            'disciplinary_actions',
            'staff_id',
            'disciplinary_actions_staff_index'
        );


        // MEETINGS
        $this->addIndex(
            'meetings',
            ['report_id', 'meeting_date'],
            'meetings_report_date_index'
        );

        $this->addIndex(
            'meetings',
            'scheduled_by',
            'meetings_scheduled_by_index'
        );


        // MEETING PARTICIPANTS
        $this->addIndex(
            'meeting_participants',
            ['meeting_id', 'student_id'],
            'meeting_participants_meeting_student_index'
        );

        $this->addIndex(
            'meeting_participants',
            ['meeting_id', 'user_id'],
            'meeting_participants_meeting_user_index'
        );


        // INBOXES
        $this->addIndex(
            'inboxes',
            ['receiver_id', 'is_read', 'created_at'],
            'inboxes_receiver_read_created_index'
        );

        $this->addIndex(
            'inboxes',
            ['sender_id', 'created_at'],
            'inboxes_sender_created_index'
        );


        // AI ANALYSES
        $this->addIndex(
            'ai_analyses',
            ['periodicity', 'created_at'],
            'ai_analyses_periodicity_created_index'
        );

        $this->addIndex(
            'ai_analyses',
            ['position_id', 'school_year_id'],
            'ai_analyses_position_school_year_index'
        );


        // USER LOGIN HISTORIES
        $this->addIndex(
            'user_login_histories',
            ['user_id', 'login_time'],
            'user_login_histories_user_login_index'
        );

        $this->addIndex(
            'user_login_histories',
            ['ip_address', 'login_time'],
            'user_login_histories_ip_login_index'
        );


        // SECURITY EVENTS
        $this->addIndex(
            'security_events',
            ['user_id', 'created_at'],
            'security_events_user_created_index'
        );

        $this->addIndex(
            'security_events',
            ['status', 'created_at'],
            'security_events_status_created_index'
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexes = [
            'grade_sections_adviser_grade_section_index',
            'grade_sections_grade_section_index',

            'school_years_is_active_index',

            'enrollments_student_current_latest_index',
            'enrollments_section_current_student_index',

            'positions_position_name_index',

            'staff_position_staff_assigned_index',
            'staff_position_position_staff_index',

            'student_parent_guardian_parent_student_index',
            'student_parent_guardian_student_parent_index',

            'incident_categories_active_name_index',

            'reports_created_at_index',
            'reports_updated_at_index',
            'reports_reported_by_index',
            'reports_status_created_index',
            'reports_category_created_index',

            'report_student_report_involvement_student_index',
            'report_student_student_involvement_report_index',

            'report_evidence_report_created_index',
            'report_evidence_uploaded_by_index',

            'report_updates_report_created_updated_index',
            'report_updates_status_index',
            'report_updates_updated_by_index',

            'report_assignments_report_latest_index',
            'report_assignments_assignee_active_index',
            'report_assignments_assigner_report_index',

            'disciplinary_actions_student_created_index',
            'disciplinary_actions_report_created_index',
            'disciplinary_actions_staff_index',

            'meetings_report_date_index',
            'meetings_scheduled_by_index',

            'meeting_participants_meeting_student_index',
            'meeting_participants_meeting_user_index',

            'inboxes_receiver_read_created_index',
            'inboxes_sender_created_index',

            'ai_analyses_periodicity_created_index',
            'ai_analyses_position_school_year_index',

            'user_login_histories_user_login_index',
            'user_login_histories_ip_login_index',

            'security_events_user_created_index',
            'security_events_status_created_index',
        ];

        foreach ($indexes as $index) {
            foreach ([
                'grade_sections',
                'school_years',
                'enrollments',
                'positions',
                'staff_position',
                'student_parent_guardian',
                'incident_categories',
                'reports',
                'report_student',
                'report_evidence',
                'report_updates',
                'report_assignments',
                'disciplinary_actions',
                'meetings',
                'meeting_participants',
                'inboxes',
                'ai_analyses',
                'user_login_histories',
                'security_events',
            ] as $tableName) {
                if (Schema::hasTable($tableName) && Schema::hasIndex($tableName, $index)) {
                    Schema::table($tableName, function (Blueprint $table) use ($index) {
                        $table->dropIndex($index);
                    });

                    break;
                }
            }
        }
    }
};
