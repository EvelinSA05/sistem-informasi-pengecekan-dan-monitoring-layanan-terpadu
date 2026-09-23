<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sql = \App\Models\PeriodService::where('tester_id', 2)
    ->where('status', 'pending')
    ->whereHas('period', function ($q) {
        $q->where('status', 'proses');
        $q->where(function ($query) {
            $query->whereNull('deadline_date')
                  ->orWhere('deadline_date', '>=', \Carbon\Carbon::today()->toDateString());
        });
    })->toSql();

echo "SQL: " . $sql . "\n";
