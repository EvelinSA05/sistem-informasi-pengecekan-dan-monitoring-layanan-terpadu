<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserNotificationRead extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'notification_key',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    public static function ensureSchema(): void
    {
        static $ensured = false;
        if ($ensured) {
            return;
        }
        $ensured = true;

        try {
            if (!Schema::hasColumn('users', 'notifications_read_at')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->timestamp('notifications_read_at')->nullable();
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
        } catch (\Throwable $e) {
            // Abaikan jika terjadi race condition DDL atau migrasi sudah berjalan
        }
    }
}
