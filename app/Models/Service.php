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
        'status',
        'notes',
        'synced_at',
    ];

    /**
     * Konstanta nilai status yang valid.
     */
    const STATUS_AKTIF             = 'aktif';
    const STATUS_TIDAK_AKTIF       = 'tidak_aktif';
    const STATUS_BUTUH_KONFIRMASI  = 'butuh_konfirmasi';

    const VALID_STATUSES = [
        self::STATUS_AKTIF,
        self::STATUS_TIDAK_AKTIF,
        self::STATUS_BUTUH_KONFIRMASI,
    ];

    public function periodServices()
    {
        return $this->hasMany(PeriodService::class, 'service_id');
    }
}
