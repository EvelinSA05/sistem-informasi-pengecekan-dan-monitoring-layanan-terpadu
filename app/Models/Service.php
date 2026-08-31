<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'source_service_id',
        'service_name',
        'service_url',
        'is_active',
        'synced_at',
    ];

    public function periodServices()
    {
        return $this->hasMany(PeriodService::class, 'service_id');
    }
}
