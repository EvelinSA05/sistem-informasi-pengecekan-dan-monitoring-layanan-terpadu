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
    ];

    public function periodService(): BelongsTo
    {
        return $this->belongsTo(PeriodService::class);
    }

    public function tester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tester_id');
    }
}
