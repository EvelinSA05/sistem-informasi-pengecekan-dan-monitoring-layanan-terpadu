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
        'attachment',
        'checked_at',
    ];

    protected $appends = ['attachment_url'];

    public function getAttachmentUrlAttribute(): ?string
    {
        return $this->attachment ? asset('storage/' . $this->attachment) : null;
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
}
