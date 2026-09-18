<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Vercel Serverless Functions have a read-only filesystem except for /tmp
$app->useStoragePath('/tmp');

$request = Illuminate\Http\Request::capture();
$response = $app->handleRequest($request);
$response->send();
$app->terminate($request, $response);
