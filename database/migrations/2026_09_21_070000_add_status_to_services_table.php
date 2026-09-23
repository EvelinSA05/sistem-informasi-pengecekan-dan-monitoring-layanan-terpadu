<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahkan kolom status (3 nilai: aktif, tidak_aktif, butuh_konfirmasi)
     * dan mapping data lama dari is_active boolean.
     */
    public function up(): void
    {
        // Idempotent: hanya tambah kolom jika belum ada
        if (!Schema::hasColumn('services', 'status')) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('status', 30)->nullable()->after('is_active');
            });
        }

        // Mapping data lama: is_active=1 → aktif, is_active=0 → tidak_aktif
        DB::statement("UPDATE services SET status = CASE WHEN is_active = true THEN 'aktif' ELSE 'tidak_aktif' END WHERE status IS NULL OR status = '' OR status = 'aktif'");

        // Pastikan tidak ada NULL tersisa
        DB::statement("UPDATE services SET status = 'aktif' WHERE status IS NULL OR status = ''");

        // Pastikan kolom NOT NULL dengan default
        Schema::table('services', function (Blueprint $table) {
            $table->string('status', 30)->default('aktif')->nullable(false)->change();
        });
    }

    /**
     * Kembalikan ke sebelum migration (hapus kolom status).
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
