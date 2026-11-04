<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCheckLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_service_id',
        'tester_id',
        'status',
        'description',
        'attachment',
        'attachment_data',
        'attachment_mime',
    ];

    public const LIST_COLUMNS = [
        'id',
        'period_service_id',
        'tester_id',
        'status',
        'description',
        'attachment',
        'attachment_mime',
        'created_at',
        'updated_at',
    ];

    protected $hidden = [
        'attachment_data',
    ];

    protected $appends = ['attachment_url'];

    /**
     * Scope untuk mengambil kolom tanpa blob attachment_data.
     */
    public function scopeWithoutAttachmentData($query)
    {
        return $query->select(self::LIST_COLUMNS);
    }

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;
        try {
            if ($this->period_service_id) {
                return route('periods.attachment.download', ['periodService' => $this->period_service_id, 'inline' => 1]);
            }
        } catch (\Throwable $e) {
            // fallback
        }
        return asset('storage/' . $this->attachment);
    }

    public function periodService(): BelongsTo
    {
        return $this->belongsTo(PeriodService::class);
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_id');
    }
}
