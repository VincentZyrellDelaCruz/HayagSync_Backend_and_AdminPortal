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
        Schema::create('report_evidence', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->text('caption')->nullable();
            $table->string('hash_signature', 64)->nullable();
            $table->string('evidence_verification_state')->default('Unverified'); // Verified, Suspected AI Generated, Suspected Google Image, Unverified
            $table->text('evidence_verification_details')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_evidence');
    }
};
