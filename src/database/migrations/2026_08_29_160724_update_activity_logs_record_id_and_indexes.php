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
        if (!Schema::hasColumn('activity_logs', 'record_id')) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->string('record_id')
                    ->nullable()
                    ->after('module');
            });
        }

        $indexes = collect(
            DB::select('SHOW INDEX FROM activity_logs')
        );

        $uniqueRecordIndex = $indexes
            ->first(function ($index) {
                return $index->Key_name === 'activity_logs_record_id_unique';
            });

        if ($uniqueRecordIndex) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->dropUnique(
                    'activity_logs_record_id_unique'
                );
            });
        }

        $recordIndexExists = $indexes->contains(
            function ($index) {
                return $index->Key_name === 'activity_logs_record_id_index';
            }
        );

        if (!$recordIndexExists) {
            Schema::table('activity_logs', function (Blueprint $table) {
                $table->index(
                    'record_id',
                    'activity_logs_record_id_index'
                );
            });
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->index(
                'action_type',
                'activity_logs_action_type_index'
            );

            $table->index(
                'module',
                'activity_logs_module_index'
            );

            $table->index(
                'ip_address',
                'activity_logs_ip_address_index'
            );

            $table->index(
                'created_at',
                'activity_logs_created_at_index'
            );

            $table->index(
                ['user_id', 'created_at'],
                'activity_logs_user_created_index'
            );

            $table->index(
                ['module', 'action_type'],
                'activity_logs_module_action_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropIndex(
                'activity_logs_module_action_index'
            );

            $table->dropIndex(
                'activity_logs_user_created_index'
            );

            $table->dropIndex(
                'activity_logs_created_at_index'
            );

            $table->dropIndex(
                'activity_logs_ip_address_index'
            );

            $table->dropIndex(
                'activity_logs_module_index'
            );

            $table->dropIndex(
                'activity_logs_action_type_index'
            );

            $table->dropIndex(
                'activity_logs_record_id_index'
            );
        });
    }
};
