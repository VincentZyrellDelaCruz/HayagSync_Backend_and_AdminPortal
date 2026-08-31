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
            $table->string('report_code')->unique();
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
