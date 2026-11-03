<?php

use App\Models\CheckingPeriod;
use App\Models\PeriodService;
use App\Models\Service;
use App\Models\ServiceCheckLog;
use App\Models\ServiceIssue;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('period_services') && Schema::hasTable('services')) {
            // 1. Dapatkan daftar ID layanan valid yang tersinkronisasi dari Majadigi
            $validMasterServiceIds = DB::table('services')
                ->whereNotNull('synced_at')
                ->pluck('id')
                ->all();

            // 2. Cari seluruh period_services yang tidak valid (service_id null atau tidak terdaftar di layanan tersinkronisasi)
            $invalidPeriodServices = DB::table('period_services')
                ->where(function ($query) use ($validMasterServiceIds) {
                    $query->whereNull('service_id')
                          ->orWhereNotIn('service_id', $validMasterServiceIds);
                })
                ->pluck('id')
                ->all();

            if (!empty($invalidPeriodServices)) {
                if (Schema::hasTable('service_issues')) {
                    DB::table('service_issues')
                        ->whereIn('period_service_id', $invalidPeriodServices)
                        ->delete();
                }

                if (Schema::hasTable('service_check_logs')) {
                    DB::table('service_check_logs')
                        ->whereIn('period_service_id', $invalidPeriodServices)
                        ->delete();
                }

                DB::table('period_services')
                    ->whereIn('id', $invalidPeriodServices)
                    ->delete();
            }

            // 3. Sinkronisasikan seluruh periode yang berstatus 'proses' agar memiliki seluruh layanan aktif Majadigi
            if (Schema::hasTable('checking_periods')) {
                $activePeriods = CheckingPeriod::where('status', 'proses')->get();
                foreach ($activePeriods as $period) {
                    $period->syncWithMasterServices();
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data pembersihan dummy tidak perlu di-restore
    }
};
