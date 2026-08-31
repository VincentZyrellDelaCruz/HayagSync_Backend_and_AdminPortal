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
        Schema::create('report_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('assigned_to')->nullable()->references('user_id')->on('staff')->onDelete('cascade');
            $table->foreignUuid('assigned_by')->nullable()->references('user_id')->on('staff')->onDelete('cascade');
            $table->string('level'); // 1 (Teacher/Adviser) to 4 (OSD)
            /* $table->text('reason'); */
            $table->timestamp('assigned_at');
            $table->timestamp('ended_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_assignments');
    }
};
