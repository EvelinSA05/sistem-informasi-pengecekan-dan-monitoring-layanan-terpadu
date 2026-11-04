<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('service_issues')) {
            Schema::create('service_issues', function (Blueprint $table) {
                $table->id();
                $table->foreignId('layanan_id')->constrained('services')->cascadeOnDelete();
                $table->foreignId('periode_id')->constrained('checking_periods')->cascadeOnDelete();
                $table->foreignId('period_service_id')->constrained('period_services')->cascadeOnDelete();
                $table->boolean('has_notif')->default(false);
                $table->boolean('has_solved')->default(false);
                $table->timestamps();

                $table->index(['layanan_id', 'has_solved']);
                $table->index('periode_id');
                $table->index('period_service_id');
            });
        }

        // Buat view layanan_terkendala untuk kompatibilitas nama tabel Bahasa Indonesia & Inggris
        DB::statement('DROP VIEW IF EXISTS layanan_terkendala');
        DB::statement('CREATE VIEW layanan_terkendala AS SELECT * FROM service_issues');

        // Backfill data kendala dari period_services yang sudah berstatus gangguan sebelumnya
        $existingIssues = DB::table('period_services')
            ->where('status', 'gangguan')
            ->get();

        foreach ($existingIssues as $issue) {
            DB::table('service_issues')->updateOrInsert(
                ['period_service_id' => $issue->id],
                [
                    'layanan_id'  => $issue->service_id,
                    'periode_id'  => $issue->period_id,
                    'has_notif'   => false,
                    'has_solved'  => false,
                    'created_at'  => $issue->checked_at ?? now(),
                    'updated_at'  => $issue->checked_at ?? now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS layanan_terkendala');
        Schema::dropIfExists('service_issues');
    }
};
