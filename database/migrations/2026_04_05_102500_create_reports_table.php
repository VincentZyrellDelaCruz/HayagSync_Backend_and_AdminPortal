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
        Schema::create('reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reported_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('incident_categories')->onDelete('cascade');
            $table->foreignId('current_status_id')->constrained('report_statuses')->onDelete('cascade');
            $table->string('incident_title');
            $table->longText('description');
            $table->string('location')->nullable();
            $table->date('incident_date');
            $table->time('incident_time')->nullable();
            $table->text('ai_summary')->nullable();
            $table->timestamps();
            $table->softDeletes();

            /* $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->string('parent_name');
            $table->string('student_name');
            $table->string('student_section');
            $table->string('student_grade');
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->dateTime('incident_date');
            $table->string('incident_location');
            $table->string('evidence_url')->nullable(); */

            // Alleged Bully & Witnesses
            /* $table->string('bully_name')->nullable();
            $table->string('bully_grade_section')->nullable();
            $table->text('witnesses')->nullable(); */

            // Lifecycle status
            // $table->string('status')->default('Pending'); // Pending, Under Review, Scheduled, Escalated, Resolved

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
