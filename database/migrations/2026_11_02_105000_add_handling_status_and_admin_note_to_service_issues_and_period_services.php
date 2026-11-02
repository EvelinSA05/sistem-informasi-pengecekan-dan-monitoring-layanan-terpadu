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
        if (Schema::hasTable('service_issues')) {
            Schema::table('service_issues', function (Blueprint $table) {
                if (!Schema::hasColumn('service_issues', 'handling_status')) {
                    $table->string('handling_status', 50)->default('belum_ditindaklanjuti')->after('has_solved');
                }
                if (!Schema::hasColumn('service_issues', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('handling_status');
                }
                if (!Schema::hasColumn('service_issues', 'admin_handled_at')) {
                    $table->timestamp('admin_handled_at')->nullable()->after('admin_note');
                }
                if (!Schema::hasColumn('service_issues', 'admin_id')) {
                    $table->foreignId('admin_id')->nullable()->after('admin_handled_at')->constrained('users')->nullOnDelete();
                }
            });

            // Backfill existing data
            DB::table('service_issues')->where('has_solved', true)->update([
                'handling_status' => 'selesai',
            ]);
        }

        if (Schema::hasTable('period_services')) {
            Schema::table('period_services', function (Blueprint $table) {
                if (!Schema::hasColumn('period_services', 'admin_note')) {
                    $table->text('admin_note')->nullable()->after('description');
                }
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
                if (Schema::hasColumn('period_services', 'admin_note')) {
                    $table->dropColumn('admin_note');
                }
            });
        }

        if (Schema::hasTable('service_issues')) {
            Schema::table('service_issues', function (Blueprint $table) {
                if (Schema::hasColumn('service_issues', 'admin_id')) {
                    $table->dropForeign(['admin_id']);
                    $table->dropColumn('admin_id');
                }
                if (Schema::hasColumn('service_issues', 'admin_handled_at')) {
                    $table->dropColumn('admin_handled_at');
                }
                if (Schema::hasColumn('service_issues', 'admin_note')) {
                    $table->dropColumn('admin_note');
                }
                if (Schema::hasColumn('service_issues', 'handling_status')) {
                    $table->dropColumn('handling_status');
                }
            });
        }
    }
};
