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

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        if (!$this->attachment) return null;
        // Serve via dedicated route if has stored data, otherwise fallback to storage
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
