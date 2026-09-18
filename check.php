<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$q1 = App\Models\Service::where('is_active', true)->count();
$q2 = App\Models\Service::where('is_active', 1)->count();
$q3 = App\Models\Service::where('is_active', '1')->count();

echo "true: $q1, 1: $q2, '1': $q3\n";

$s1 = App\Models\Service::where('is_active', false)->count();
$s2 = App\Models\Service::where('is_active', 0)->count();
$s3 = App\Models\Service::where('is_active', '0')->count();

echo "false: $s1, 0: $s2, '0': $s3\n";

$first = App\Models\Service::first();
echo "First is_active type: " . gettype($first->is_active) . ", value: " . $first->is_active . "\n";
