<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CheckingPeriod extends Model
{
    protected $table = 'checking_periods';

    protected $fillable = [
        'date',
        'deadline_date',
        'deadline',
        'tanggal_periode',
        'status',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'deadline_date' => 'date',
    ];

    /**
     * Otomatis sinkronisasi periode yang berstatus 'proses' namun sudah melewati deadline
     * menjadi 'selesai' (nonaktif).
     */
    public static function syncOverdueStatuses(): void
    {
        static::where('status', 'proses')
            ->whereNotNull('deadline_date')
            ->where('deadline_date', '<', \Carbon\Carbon::today()->toDateString())
            ->update(['status' => 'selesai']);
    }

    /**
     * Cek apakah periode telah melewati batas deadline.
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->deadline_date) {
            return false;
        }
        return \Carbon\Carbon::now()->isAfter(\Carbon\Carbon::parse($this->deadline_date)->endOfDay());
    }

    /**
     * Mutator untuk memastikan kolom date disimpan dalam format Y-m-d murni (kompatibel PostgreSQL & SQLite)
     */
    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

    /**
     * Mutator untuk memastikan kolom deadline_date disimpan dalam format Y-m-d murni (kompatibel PostgreSQL & SQLite)
     */
    public function setDeadlineDateAttribute($value): void
    {
        $this->attributes['deadline_date'] = $value ? \Carbon\Carbon::parse($value)->format('Y-m-d') : null;
    }

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
        $this->setDateAttribute($value);
    }

    /**
     * Alias accessor untuk deadline
     */
    public function getDeadlineAttribute()
    {
        return $this->deadline_date ? $this->deadline_date->format('Y-m-d') : null;
    }

    /**
     * Alias mutator untuk deadline
     */
    public function setDeadlineAttribute($value)
    {
        $this->setDeadlineDateAttribute($value);
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

    /**
     * Relasi ke ServiceIssue (layanan yang terkendala pada periode ini)
     */
    public function serviceIssues()
    {
        return $this->hasMany(ServiceIssue::class, 'periode_id');
    }

    /**
     * Sinkronisasikan layanan pada periode ini dengan master layanan aktif yang tersinkronisasi dari Majadigi.
     */
    public function syncWithMasterServices(): void
    {
        // 1. Dapatkan daftar master layanan aktif yang tersinkronisasi dari Majadigi
        $activeMasterServices = Service::where('is_active', true)
            ->whereNotNull('synced_at')
            ->orderBy('id')
            ->get();

        $activeMasterServiceIds = $activeMasterServices->pluck('id')->all();

        // 2. Hapus seluruh period_services pada periode ini yang BUKAN merupakan layanan tersinkronisasi Majadigi
        $invalidPsIds = PeriodService::where('period_id', $this->id)
            ->where(function ($q) use ($activeMasterServiceIds) {
                $q->whereNull('service_id')
                  ->orWhereNotIn('service_id', $activeMasterServiceIds);
            })
            ->pluck('id')
            ->all();

        if (!empty($invalidPsIds)) {
            ServiceIssue::whereIn('period_service_id', $invalidPsIds)->delete();
            ServiceCheckLog::whereIn('period_service_id', $invalidPsIds)->delete();
            PeriodService::whereIn('id', $invalidPsIds)->delete();
        }

        // 3. Pastikan seluruh layanan aktif Majadigi ada di periode ini (hanya ambil kolom service_id tanpa blob attachment_data)
        $existingServiceIds = PeriodService::where('period_id', $this->id)
            ->pluck('service_id')
            ->flip();

        $testerIds = $this->periodTesters()->pluck('tester_id')->all();
        if (empty($testerIds)) {
            $testerIds = User::where('role', 'tester')->pluck('id')->all();
        }

        if (!empty($testerIds) && $activeMasterServices->isNotEmpty()) {
            $testerCount = count($testerIds);
            foreach ($activeMasterServices as $index => $service) {
                if (!$existingServiceIds->has($service->id)) {
                    $assignedTesterId = $testerIds[$index % $testerCount];
                    PeriodService::create([
                        'period_id'   => $this->id,
                        'service_id'  => $service->id,
                        'tester_id'   => $assignedTesterId,
                        'status'      => 'pending',
                        'description' => null,
                        'checked_at'  => null,
                    ]);
                }
            }
        }
    }

    /**
     * Sinkronisasikan seluruh periode aktif ('proses') dengan Master Layanan Majadigi.
     */
    public static function syncAllActivePeriodsWithMasterServices(): void
    {
        $activePeriods = static::where('status', 'proses')->get();
        foreach ($activePeriods as $period) {
            $period->syncWithMasterServices();
        }
    }
}
