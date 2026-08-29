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
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('middle_name')->nullable();
            $table->string('suffix')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Others']);
            $table->date('birthdate');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone_number')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->string('status')->default('Active');
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');  
            $table->index('created_at');  
            $table->index(['last_name', 'first_name']);  
            $table->index('deleted_at');  


            // Custom Roles and student links
           /*  $table->enum('role', ['parent', 'adviser', 'principal', 'ministrong_tagasubaybay', 'osd'])->default('parent');
            $table->string('avatar_url')->nullable(); */

            // For advisers
            // $table->string('assigned_section')->nullable();

            // For verified parents
            /* $table->string('student_name')->nullable();
            $table->string('student_grade')->nullable();
            $table->string('student_section')->nullable();
            $table->string('academic_year')->nullable(); */
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
