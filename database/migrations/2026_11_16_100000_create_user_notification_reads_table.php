<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'notifications_read_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->timestamp('notifications_read_at')->nullable()->after('remember_token');
            });
        }

        if (!Schema::hasTable('user_notification_reads')) {
            Schema::create('user_notification_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->string('notification_key', 120);
                $table->timestamp('read_at')->useCurrent();
                $table->unique(['user_id', 'notification_key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_reads');

        if (Schema::hasColumn('users', 'notifications_read_at')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notifications_read_at');
            });
        }
    }
};
