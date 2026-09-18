<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $table = 'services';

    protected $fillable = [
        'source_service_id',
        'service_name',
        'category',
        'level',
        'icon',
        'service_url',
        'deskripsi_layanan',
        'tester_instruction',
        'is_active',
        'notes',
        'synced_at',
    ];

    public function periodServices()
    {
        return $this->hasMany(PeriodService::class, 'service_id');
    }
}
