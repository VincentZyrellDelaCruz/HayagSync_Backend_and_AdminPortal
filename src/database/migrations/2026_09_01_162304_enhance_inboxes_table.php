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
        Schema::table('inboxes', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->uuid('sender_id')->nullable()->change();
            $table->text('message')->nullable()->change();
            $table->string('notification_type')->default('system')->after('message')->index();
            $table->string('priority')->default('normal')->after('notification_type')->index();
            $table->string('action_url')->nullable()->after('priority');
            $table->json('data')->nullable()->after('action_url');
            $table->timestamp('read_at')->nullable()->after('is_read');
            $table->timestamp('expires_at')->nullable()->after('read_at')->index();
            $table->index(['receiver_id', 'is_read', 'created_at'],'inboxes_receiver_read_created_index');
            $table->index(['receiver_id', 'expires_at'],'inboxes_receiver_expiration_index');
            $table->foreign('sender_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inboxes', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropIndex('inboxes_receiver_expiration_index');
            $table->dropIndex('inboxes_receiver_read_created_index');
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['notification_type']);
            $table->dropColumn(['notification_type','priority','action_url','data','read_at','expires_at',]);
            $table->string('message')->nullable()->change();
            $table->uuid('sender_id')->nullable(false)->change();
            $table->foreign('sender_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
