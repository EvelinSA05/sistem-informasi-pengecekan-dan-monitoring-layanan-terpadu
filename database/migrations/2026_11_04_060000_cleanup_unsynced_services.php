<?php

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
        if (Schema::hasTable('services')) {
            // Dapatkan ID seluruh layanan yang tidak tersinkronisasi dari Majadigi (synced_at IS NULL)
            $unsyncedServiceIds = DB::table('services')
                ->whereNull('synced_at')
                ->pluck('id')
                ->all();

            if (!empty($unsyncedServiceIds)) {
                // Dapatkan ID period_services terkait
                $periodServiceIds = [];
                if (Schema::hasTable('period_services')) {
                    $periodServiceIds = DB::table('period_services')
                        ->whereIn('service_id', $unsyncedServiceIds)
                        ->pluck('id')
                        ->all();
                }

                // Hapus relasi service_issues terkait
                if (Schema::hasTable('service_issues')) {
                    $query = DB::table('service_issues')->whereIn('layanan_id', $unsyncedServiceIds);
                    if (!empty($periodServiceIds)) {
                        $query->orWhereIn('period_service_id', $periodServiceIds);
                    }
                    $query->delete();
                }

                // Hapus relasi service_check_logs terkait
                if (!empty($periodServiceIds) && Schema::hasTable('service_check_logs')) {
                    DB::table('service_check_logs')
                        ->whereIn('period_service_id', $periodServiceIds)
                        ->delete();
                }

                // Hapus period_services terkait
                if (!empty($periodServiceIds)) {
                    DB::table('period_services')
                        ->whereIn('id', $periodServiceIds)
                        ->delete();
                }

                // Hapus master layanan yang tidak tersinkronisasi
                DB::table('services')
                    ->whereIn('id', $unsyncedServiceIds)
                    ->delete();
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
