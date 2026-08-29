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
        Schema::create('report_appeals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('report_id')->constrained()->restrictOnDelete();
            $table->foreignUuid('appealed_by')->constrained('users')->onDelete('cascade');
            $table->text('reason')->nullable();
            $table->string('status')->default('Pending');
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_appeals');
    }
};
