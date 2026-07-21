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
        Schema::create('meetings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignUuid('scheduled_by')->constrained('users')->onDelete('cascade');
            $table->dateTime('meeting_date');
            $table->text('notes')->nullable();
            $table->enum('status', ['Active', 'Canceled', 'Finished']);
            // $table->string('meeting_type'); // Virtual, In-Person
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
