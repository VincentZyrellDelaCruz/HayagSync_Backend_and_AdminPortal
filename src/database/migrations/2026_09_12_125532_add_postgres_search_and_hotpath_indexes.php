<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // SUBSTRING-SEARCH SUPPPORT FOR LIKE /ILIKE
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');

        DB::statement('CREATE INDEX IF NOT EXISTS users_first_name_trgm_index ON users USING gin (first_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_last_name_trgm_index ON users USING gin (last_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS users_email_trgm_index ON users USING gin (email gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS students_first_name_trgm_index ON students USING gin (first_name gin_trgm_ops)');
        DB::statement('CREATE INDEX IF NOT EXISTS students_last_name_trgm_index ON students USING gin (last_name gin_trgm_ops)');

        DB::statement('CREATE INDEX IF NOT EXISTS reports_reported_by_created_at_active_index ON reports (reported_by, created_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS reports_status_updated_at_active_index ON reports (current_status_id, updated_at DESC) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX IF NOT EXISTS reports_category_created_at_active_index ON reports (category_id, created_at DESC) WHERE deleted_at IS NULL');

        // CURRENT ENROLLMENT LOOKUPS
        if (Schema::hasTable('enrollments') && !Schema::hasIndex('enrollments', 'enrollments_student_status_ended_enrolled_index')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(
                    ['student_id', 'status', 'ended_at', 'enrolled_at'],
                    'enrollments_student_status_ended_enrolled_index'
                );
            });
        }

        if (Schema::hasTable('enrollments') && !Schema::hasIndex('enrollments', 'enrollments_section_status_ended_student_index')) {
            Schema::table('enrollments', function (Blueprint $table) {
                $table->index(
                    ['grade_section_id', 'status', 'ended_at', 'student_id'],
                    'enrollments_section_status_ended_student_index'
                );
            });
        }

        // REPORT / STUDENT RELATIONSHIP LOOKUPS
        if (Schema::hasTable('report_student') && !Schema::hasIndex('report_student', 'report_student_student_report_index')) {
            Schema::table('report_student', function (Blueprint $table) {
                $table->index(
                    ['student_id', 'report_id'],
                    'report_student_student_report_index'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_first_name_trgm_index');
        DB::statement('DROP INDEX IF EXISTS users_last_name_trgm_index');
        DB::statement('DROP INDEX IF EXISTS users_email_trgm_index');
        DB::statement('DROP INDEX IF EXISTS students_first_name_trgm_index');
        DB::statement('DROP INDEX IF EXISTS students_last_name_trgm_index');

        DB::statement('DROP INDEX IF EXISTS reports_reported_by_created_at_active_index');
        DB::statement('DROP INDEX IF EXISTS reports_status_updated_at_active_index');
        DB::statement('DROP INDEX IF EXISTS reports_category_created_at_active_index');

        if (Schema::hasTable('enrollments') && Schema::hasIndex('enrollments', 'enrollments_student_status_ended_enrolled_index')) {
            Schema::table('enrollments', fn (Blueprint $table) => $table->dropIndex('enrollments_student_status_ended_enrolled_index'));
        }

        if (Schema::hasTable('enrollments') && Schema::hasIndex('enrollments', 'enrollments_section_status_ended_student_index')) {
            Schema::table('enrollments', fn (Blueprint $table) => $table->dropIndex('enrollments_section_status_ended_student_index'));
        }

        if (Schema::hasTable('report_student') && Schema::hasIndex('report_student', 'report_student_student_report_index')) {
            Schema::table('report_student', fn (Blueprint $table) => $table->dropIndex('report_student_student_report_index'));
        }
    }
};
