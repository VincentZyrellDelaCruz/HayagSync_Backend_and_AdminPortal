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
            $table->id();
            $table->foreignId('parent_id')->constrained('users')->onDelete('cascade');
            $table->string('parent_name');
            $table->string('student_name');
            $table->string('student_section');
            $table->string('student_grade');
            $table->string('title');
            $table->text('description');
            $table->string('category');
            $table->dateTime('incident_date');
            $table->string('incident_location');
            $table->string('evidence_url')->nullable();
            
            // Alleged Bully & Witnesses
            $table->string('bully_name')->nullable();
            $table->string('bully_grade_section')->nullable();
            $table->text('witnesses')->nullable();
            
            // AI Verification Details
            $table->string('evidence_verification_state')->default('Unverified'); // Verified, Suspected AI Generated, Suspected Google Image, Unverified
            $table->text('evidence_verification_details')->nullable();
            
            // AI summaries
            $table->text('ai_summary')->nullable();
            
            // Lifecycle status
            $table->string('status')->default('Pending'); // Pending, Under Review, Scheduled, Escalated, Resolved
            
            // Disciplinary resolutions
            $table->string('discipline_action')->nullable();
            $table->text('discipline_notes')->nullable();
            
            // Appeals
            $table->text('appealed_reason')->nullable();
            $table->integer('appeal_count')->default(0);
            
            $table->timestamps();
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
