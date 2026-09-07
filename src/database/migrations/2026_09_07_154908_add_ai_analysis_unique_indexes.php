<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            WITH ranked AS (
                SELECT
                    ctid,
                    ROW_NUMBER() OVER (
                        PARTITION BY
                            periodicity,
                            period_label,
                            position_id,
                            school_year_id
                        ORDER BY
                            created_at DESC,
                            id DESC
                    ) AS row_number
                FROM ai_analyses
            )
            DELETE FROM ai_analyses
            WHERE ctid IN (
                SELECT ctid
                FROM ranked
                WHERE row_number > 1
            )
        ");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS
            ai_analyses_period_position_year_unique
            ON ai_analyses (
                periodicity,
                period_label,
                position_id,
                school_year_id
            )
            WHERE school_year_id IS NOT NULL
        ");

        DB::statement("
            CREATE UNIQUE INDEX IF NOT EXISTS
            ai_analyses_period_position_no_year_unique
            ON ai_analyses (
                periodicity,
                period_label,
                position_id
            )
            WHERE school_year_id IS NULL
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS
            ai_analyses_position_period_created_at_index
            ON ai_analyses (
                position_id,
                periodicity,
                created_at DESC
            )
        ");

        DB::statement("
            CREATE INDEX IF NOT EXISTS
            reports_status_incident_date_index
            ON reports (
                current_status_id,
                incident_date
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX IF EXISTS
            ai_analyses_period_position_year_unique
        ");

        DB::statement("
            DROP INDEX IF EXISTS
            ai_analyses_period_position_no_year_unique
        ");

        DB::statement("
            DROP INDEX IF EXISTS
            ai_analyses_position_period_created_at_index
        ");

        DB::statement("
            DROP INDEX IF EXISTS
            reports_status_incident_date_index
        ");
    }
};
