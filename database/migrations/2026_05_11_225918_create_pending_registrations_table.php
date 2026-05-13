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
        Schema::create('pending_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Others']);
            $table->date('birthdate');
            $table->string('occupation');
            $table->string('phone_number')->nullable();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->string('relationship');
            $table->enum('status', ['Approved', 'Rejected', 'Pending'])->default('Pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pendings');
    }
};
