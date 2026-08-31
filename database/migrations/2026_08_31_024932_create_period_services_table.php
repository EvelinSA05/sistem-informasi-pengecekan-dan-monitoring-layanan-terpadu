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
        Schema::create('period_services', function (Blueprint $table) {
    $table->id();

    $table->foreignId('period_id')
        ->constrained('checking_periods')
        ->onDelete('cascade');

    $table->foreignId('service_id')
        ->nullable()
        ->constrained('services')
        ->onDelete('set null');

    $table->foreignId('tester_id')
        ->nullable()
        ->constrained('users')
        ->onDelete('set null');

    $table->string('status', 30);
    $table->text('description')->nullable();
    $table->timestamp('checked_at')->nullable();

    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_services');
    }
};
