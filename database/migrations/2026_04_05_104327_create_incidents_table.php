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
        Schema::create('incidents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('school_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('reported_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('incident_categories')->onDelete('cascade');
            $table->foreignId('current_status_id')->constrained('incident_statuses')->onDelete('cascade');
            $table->string('incident_title');
            $table->longText('description');
            $table->dateTime('incident_datetime')->index();
            $table->string('location')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('urgency_level', ['Low', 'Medium', 'High', 'Critical'])->default('Low');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
