<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckingPeriod extends Model
{
    protected $table = 'checking_periods';

    protected $fillable = [
        'date',
        'status',
        'created_by',
    ];

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
}
