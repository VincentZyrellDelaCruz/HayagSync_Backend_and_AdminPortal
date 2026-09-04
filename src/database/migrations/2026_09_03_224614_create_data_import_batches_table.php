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
        Schema::create('data_import_batches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('initiated_by')->constrained('users')->restrictOnDelete();
            $table->string('import_type', 20);  // 'students' or 'staff'
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_hash', 64);
            $table->string('mode', 30)->default('full_roster'); // Full Roster or Reference Only
            $table->string('status', 30)->default('queued');
            $table->string('stage')->nullable();
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('invalid_rows')->default(0);
            $table->unsignedInteger('processed_rows')->default(0);
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('deactivated_count')->default(0);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['import_type', 'status', 'created_at']);
            $table->index(['initiated_by', 'created_at']);
            $table->index('file_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_import_batches');
    }
};
