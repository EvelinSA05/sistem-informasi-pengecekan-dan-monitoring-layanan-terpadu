<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodService extends Model
{
    protected $table = 'period_services';

    protected $fillable = [
        'period_id',
        'service_id',
        'tester_id',
        'status',
        'description',
        'admin_note',
        'attachment',
        'attachment_data',
        'attachment_mime',
        'checked_at',
    ];

    public const LIST_COLUMNS = [
        'id',
        'period_id',
        'service_id',
        'tester_id',
        'status',
        'description',
        'admin_note',
        'attachment',
        'attachment_mime',
        'checked_at',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'attachment_data',
    ];

    protected $appends = ['attachment_url'];

    /**
     * Scope untuk mengambil kolom tanpa blob attachment_data (mempercepat query daftar/tabel).
     */
    public function scopeWithoutAttachmentData($query)
    {
        return $query->select(self::LIST_COLUMNS);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;
        // Use the serve route instead of storage URL for Vercel compatibility
        try {
            return route('periods.attachment.download', ['periodService' => $this->id, 'inline' => 1]);
        } catch (\Throwable $e) {
            return asset('storage/' . $this->attachment);
        }
    }
    public function period()
    {
        return $this->belongsTo(CheckingPeriod::class, 'period_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function tester()
    {
        return $this->belongsTo(User::class, 'tester_id');
    }

    public function serviceIssue()
    {
        return $this->hasOne(ServiceIssue::class, 'period_service_id');
    }

    public function checkLogs()
    {
        return $this->hasMany(ServiceCheckLog::class, 'period_service_id')
            ->select(ServiceCheckLog::LIST_COLUMNS)
            ->latest();
    }
}
