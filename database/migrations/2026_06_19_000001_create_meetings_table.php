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
            $table->id();
            $table->foreignId('report_id')->constrained('reports')->onDelete('cascade');
            $table->foreignId('scheduled_by_user_id')->constrained('users')->onDelete('cascade');
            $table->string('scheduled_by_user_name');
            $table->string('scheduled_by_user_role');
            $table->dateTime('meeting_date');
            $table->text('notes')->nullable();
            $table->string('meeting_type'); // Virtual, In-Person
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
