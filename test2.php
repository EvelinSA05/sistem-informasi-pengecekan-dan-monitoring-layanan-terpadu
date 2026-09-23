<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$excludePeriodId = null;
$testers = \App\Models\User::where('role', 'tester')->get();
foreach ($testers as $tester) {
    $pendingTasksCount = \App\Models\PeriodService::where('tester_id', $tester->id)
        ->where('status', 'pending')
        ->whereHas('period', function ($q) use ($excludePeriodId) {
            $q->where('status', 'proses');
            if ($excludePeriodId) {
                $q->where('checking_periods.id', '!=', $excludePeriodId);
            }
            $q->where(function ($query) {
                $query->whereNull('deadline_date')
                      ->orWhere('deadline_date', '>=', \Carbon\Carbon::today()->toDateString());
            });
        })
        ->count();

    echo "Tester " . $tester->name . " pending: " . $pendingTasksCount . "\n";
}
