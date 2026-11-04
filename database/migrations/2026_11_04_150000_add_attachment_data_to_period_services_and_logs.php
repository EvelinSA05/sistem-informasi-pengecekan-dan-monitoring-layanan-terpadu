<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Tambahkan kolom attachment_data (base64 encoded file content)
     * agar file lampiran dapat disimpan di database dan diakses di Vercel
     * yang tidak memiliki persistent storage.
     */
    public function up(): void
    {
        if (Schema::hasTable('period_services') && !Schema::hasColumn('period_services', 'attachment_data')) {
            Schema::table('period_services', function (Blueprint $table) {
                $table->longText('attachment_data')->nullable()->after('attachment');
                $table->string('attachment_mime', 100)->nullable()->after('attachment_data');
            });
        }

        if (Schema::hasTable('service_check_logs') && !Schema::hasColumn('service_check_logs', 'attachment_data')) {
            Schema::table('service_check_logs', function (Blueprint $table) {
                $table->longText('attachment_data')->nullable()->after('attachment');
                $table->string('attachment_mime', 100)->nullable()->after('attachment_data');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('period_services')) {
            Schema::table('period_services', function (Blueprint $table) {
                if (Schema::hasColumn('period_services', 'attachment_data')) {
                    $table->dropColumn('attachment_data');
                }
                if (Schema::hasColumn('period_services', 'attachment_mime')) {
                    $table->dropColumn('attachment_mime');
                }
            });
        }

        if (Schema::hasTable('service_check_logs')) {
            Schema::table('service_check_logs', function (Blueprint $table) {
                if (Schema::hasColumn('service_check_logs', 'attachment_data')) {
                    $table->dropColumn('attachment_data');
                }
                if (Schema::hasColumn('service_check_logs', 'attachment_mime')) {
                    $table->dropColumn('attachment_mime');
                }
            });
        }
    }
};

