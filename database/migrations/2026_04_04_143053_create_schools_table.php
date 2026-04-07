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
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('school_code')->unique();
            $table->string('school_name');
            $table->string('campus_name')->nullable();
            $table->string('street_address')->default('N/A');
            $table->string('barangay');
            $table->string('city');
            $table->string('contact_number')->nullable();
            $table->string('email_address')->unique();
            $table->string('website_url')->nullable();
            $table->string('permit_number')->nullable();
            $table->string('recognition_number')->nullable();;
            $table->string('principal')->nullable();;
            $table->date('date_established')->nullable();;
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
