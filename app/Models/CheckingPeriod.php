<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckingPeriod extends Model
{
    protected $table = 'checking_periods';

    protected $fillable = [
        'date',
        'tanggal_periode',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Alias accessor untuk tanggal_periode
     */
    public function getTanggalPeriodeAttribute()
    {
        return $this->date ? $this->date->format('Y-m-d') : null;
    }

    /**
     * Alias mutator untuk tanggal_periode
     */
    public function setTanggalPeriodeAttribute($value)
    {
        $this->attributes['date'] = $value;
    }

    /**
     * Relasi ke User
     * Satu periode dibuat oleh satu Admin.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke PeriodTester
     * Satu periode dapat memiliki banyak Tester.
     */
    public function periodTesters()
    {
        return $this->hasMany(PeriodTester::class, 'period_id');
    }

    /**
     * Relasi ke PeriodService
     * Satu periode dapat memiliki banyak layanan.
     */
    public function periodServices()
    {
        return $this->hasMany(PeriodService::class, 'period_id');
    }

    /**
     * Relasi langsung ke User Tester
     */
    public function testers()
    {
        return $this->belongsToMany(User::class, 'period_testers', 'period_id', 'tester_id');
    }
}
