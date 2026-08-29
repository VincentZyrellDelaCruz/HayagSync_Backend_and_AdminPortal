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
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('grade_section_id')->constrained('grade_sections')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            
            $table->string('status')->default('Enrolled'); // Enrolled, Transferred, Dropped
            $table->date('enrolled_at')->nullable();
            $table->date('ended_at')->nullable();

            $table->timestamps();

            $table->unique(['student_id', 'grade_section_id', 'school_year_id']); 
            $table->index(['school_year_id', 'grade_section_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
