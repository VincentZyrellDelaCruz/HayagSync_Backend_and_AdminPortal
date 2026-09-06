<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (
            Schema::hasTable('data_import_batches') &&
            !Schema::hasIndex(
                'data_import_batches',
                'data_import_batches_created_at_index'
            )
        ) {
            Schema::table('data_import_batches', function (Blueprint $table) {
                $table->index(
                    'created_at',
                    'data_import_batches_created_at_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_batches') &&
            !Schema::hasIndex(
                'data_import_batches',
                'data_import_batches_user_status_created_index'
            )
        ) {
            Schema::table('data_import_batches', function (Blueprint $table) {
                $table->index(
                    [
                        'initiated_by',
                        'status',
                        'created_at',
                    ],
                    'data_import_batches_user_status_created_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_rows') &&
            !Schema::hasIndex(
                'data_import_rows',
                'data_import_rows_batch_row_index'
            )
        ) {
            Schema::table('data_import_rows', function (Blueprint $table) {
                $table->index(
                    [
                        'batch_id',
                        'row_number',
                    ],
                    'data_import_rows_batch_row_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_rows') &&
            !Schema::hasIndex(
                'data_import_rows',
                'data_import_rows_batch_validation_row_index'
            )
        ) {
            Schema::table('data_import_rows', function (Blueprint $table) {
                $table->index(
                    [
                        'batch_id',
                        'validation_status',
                        'row_number',
                    ],
                    'data_import_rows_batch_validation_row_index'
                );
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (
            Schema::hasTable('data_import_batches') &&
            Schema::hasIndex(
                'data_import_batches',
                'data_import_batches_created_at_index'
            )
        ) {
            Schema::table('data_import_batches', function (Blueprint $table) {
                $table->dropIndex(
                    'data_import_batches_created_at_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_batches') &&
            Schema::hasIndex(
                'data_import_batches',
                'data_import_batches_user_status_created_index'
            )
        ) {
            Schema::table('data_import_batches', function (Blueprint $table) {
                $table->dropIndex(
                    'data_import_batches_user_status_created_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_rows') &&
            Schema::hasIndex(
                'data_import_rows',
                'data_import_rows_batch_row_index'
            )
        ) {
            Schema::table('data_import_rows', function (Blueprint $table) {
                $table->dropIndex(
                    'data_import_rows_batch_row_index'
                );
            });
        }

        if (
            Schema::hasTable('data_import_rows') &&
            Schema::hasIndex(
                'data_import_rows',
                'data_import_rows_batch_validation_row_index'
            )
        ) {
            Schema::table('data_import_rows', function (Blueprint $table) {
                $table->dropIndex(
                    'data_import_rows_batch_validation_row_index'
                );
            });
        }
    }
};
