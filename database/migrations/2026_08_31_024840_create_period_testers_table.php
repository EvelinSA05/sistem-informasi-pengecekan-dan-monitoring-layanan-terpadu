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
        Schema::create('period_testers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('period_id')
        ->constrained('checking_periods')
        ->onDelete('cascade');
    $table->foreignId('tester_id')
        ->constrained('users')
        ->onDelete('cascade');
    $table->timestamp('created_at')->useCurrent();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_testers');
    }
};
