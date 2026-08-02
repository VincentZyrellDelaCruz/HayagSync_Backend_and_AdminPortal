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
        Schema::create('security_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->nullable()->constrained();
            $table->string('severity');
            $table->string('event_type');
            $table->text('description')->nullable();
            $table->string('ip_address');
            $table->string('location')->nullable();
            $table->string('status');
            $table->dateTime('resolved_at')->nullable();
            $table->foreignUuid('resolved_by')->nullable()->references('user_id')->on('staff')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
