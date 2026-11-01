<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('period_services') && !Schema::hasColumn('period_services', 'attachment')) {
            Schema::table('period_services', function (Blueprint $table) {
                $table->string('attachment', 500)->nullable()->after('description');
            });
        }

        if (Schema::hasTable('service_check_logs') && !Schema::hasColumn('service_check_logs', 'attachment')) {
            Schema::table('service_check_logs', function (Blueprint $table) {
                $table->string('attachment', 500)->nullable()->after('description');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('period_services') && Schema::hasColumn('period_services', 'attachment')) {
            Schema::table('period_services', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }

        if (Schema::hasTable('service_check_logs') && Schema::hasColumn('service_check_logs', 'attachment')) {
            Schema::table('service_check_logs', function (Blueprint $table) {
                $table->dropColumn('attachment');
            });
        }
    }
};
