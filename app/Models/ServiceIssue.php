<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceIssue extends Model
{
    protected $table = 'service_issues';

    protected $fillable = [
        'layanan_id',
        'periode_id',
        'period_service_id',
        'has_notif',
        'has_solved',
    ];

    protected $casts = [
        'has_notif'  => 'boolean',
        'has_solved' => 'boolean',
    ];

    /**
     * Relasi ke Master Layanan
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class, 'layanan_id');
    }

    /**
     * Relasi ke Periode Pengecekan
     */
    public function period(): BelongsTo
    {
        return $this->belongsTo(CheckingPeriod::class, 'periode_id');
    }

    /**
     * Relasi ke Penugasan Pengecekan Layanan (PeriodService)
     */
    public function periodService(): BelongsTo
    {
        return $this->belongsTo(PeriodService::class, 'period_service_id');
    }
}
