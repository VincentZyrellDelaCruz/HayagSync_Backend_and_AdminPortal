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
        Schema::create('report_student', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->string('involvement_type');
            $table->enum('finding', ['pending', 'substantiated', 'unsubstantiated', 'corroborated', 'cleared'])
                ->default('pending');
            $table->text('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_student');
    }
};
