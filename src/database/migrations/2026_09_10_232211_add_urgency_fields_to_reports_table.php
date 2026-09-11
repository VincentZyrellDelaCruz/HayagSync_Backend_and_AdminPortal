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
        Schema::table('reports', function (Blueprint $table) {
            // 'null' (ongoing), 'true', 'false'
            $table->boolean('urgent_safety_flag')->nullable()->after('ai_summary');

            // Records when the asynchronous urgency assessment completed.
            $table->timestamp('urgency_assessed_at')->nullable()->after('urgent_safety_flag');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['urgent_safety_flag', 'urgency_assessed_at']);
        });
    }
};
