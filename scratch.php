<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$req = \Illuminate\Http\Request::create('/admin/periods/3', 'PUT', ['date' => '2026-09-24', 'tester_ids' => [2]]);
// Add session to request so withErrors works
$req->setLaravelSession($app['session']->driver('array'));
$res = app()->handle($req);
dump($res->getStatusCode());
dump(session()->get('errors'));
