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
        Schema::create('data_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('batch_id')->constrained('data_import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number')->nullable();
            $table->string('identifier')->nullable();
            $table->string('validation_status', 20)->default('pending');
            $table->string('processing_status', 20)->default('pending');
            $table->string('action', 30)->nullable();
            $table->json('raw_data');
            $table->json('errors')->nullable();
            $table->json('before_data')->nullable();
            $table->json('after_data')->nullable();
            $table->timestamps();

            $table->index(['batch_id', 'validation_status']);
            $table->index(['batch_id', 'processing_status']);
            $table->index(['batch_id', 'identifier']);
            $table->unique(['batch_id', 'row_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_import_rows');
    }
};
