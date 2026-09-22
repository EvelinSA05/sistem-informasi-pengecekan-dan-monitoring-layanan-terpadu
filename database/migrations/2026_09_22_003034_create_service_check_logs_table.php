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
        Schema::create('service_check_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('period_service_id')->constrained('period_services')->cascadeOnDelete();
            $table->foreignId('tester_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pending', 'normal', 'gangguan'])->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_check_logs');
    }
};

