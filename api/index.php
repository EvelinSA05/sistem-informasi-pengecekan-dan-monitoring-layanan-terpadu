<?php
// Force environment variables for Vercel's read-only filesystem
putenv('LOG_CHANNEL=stderr');
$_ENV['LOG_CHANNEL'] = 'stderr';
$_SERVER['LOG_CHANNEL'] = 'stderr';

putenv('VIEW_COMPILED_PATH=/tmp/views');
$_ENV['VIEW_COMPILED_PATH'] = '/tmp/views';
$_SERVER['VIEW_COMPILED_PATH'] = '/tmp/views';

putenv('SESSION_DRIVER=database');
$_ENV['SESSION_DRIVER'] = 'database';
$_SERVER['SESSION_DRIVER'] = 'database';

putenv('SESSION_COOKIE=layanan_terpadu_session');
$_ENV['SESSION_COOKIE'] = 'layanan_terpadu_session';
$_SERVER['SESSION_COOKIE'] = 'layanan_terpadu_session';

putenv('APP_NAME="SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU"');
$_ENV['APP_NAME'] = 'SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU';
$_SERVER['APP_NAME'] = 'SISTEM INFORMASI PENGECEKAN DAN MONITORING LAYANAN TERPADU';

putenv('SESSION_LIFETIME=120');
$_ENV['SESSION_LIFETIME'] = '120';
$_SERVER['SESSION_LIFETIME'] = '120';

putenv('CACHE_STORE=array');
$_ENV['CACHE_STORE'] = 'array';
$_SERVER['CACHE_STORE'] = 'array';

// Force maintenance driver to prevent ArgumentCountError
putenv('APP_MAINTENANCE_DRIVER=file');
$_ENV['APP_MAINTENANCE_DRIVER'] = 'file';
$_SERVER['APP_MAINTENANCE_DRIVER'] = 'file';

putenv('BROADCAST_CONNECTION=log');
$_ENV['BROADCAST_CONNECTION'] = 'log';
$_SERVER['BROADCAST_CONNECTION'] = 'log';

putenv('QUEUE_CONNECTION=sync');
$_ENV['QUEUE_CONNECTION'] = 'sync';
$_SERVER['QUEUE_CONNECTION'] = 'sync';

// Force database connection to pgsql
putenv('DB_CONNECTION=pgsql');
$_ENV['DB_CONNECTION'] = 'pgsql';
$_SERVER['DB_CONNECTION'] = 'pgsql';

// Map any available Postgres URL to Laravel's DB_URL
$dbUrl = getenv('POSTGRES_URL') ?: getenv('DATABASE_URL') ?: getenv('DB_URL');
if ($dbUrl) {
    putenv('DB_URL=' . $dbUrl);
    $_ENV['DB_URL'] = $dbUrl;
    $_SERVER['DB_URL'] = $dbUrl;
}

// External service catalog sync URL
$syncUrl = getenv('SERVICE_SYNC_URL');
if ($syncUrl) {
    putenv('SERVICE_SYNC_URL=' . $syncUrl);
    $_ENV['SERVICE_SYNC_URL'] = $syncUrl;
    $_SERVER['SERVICE_SYNC_URL'] = $syncUrl;
}

// reCAPTCHA keys
$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY');
if ($recaptchaSiteKey) {
    putenv('RECAPTCHA_SITE_KEY=' . $recaptchaSiteKey);
    $_ENV['RECAPTCHA_SITE_KEY'] = $recaptchaSiteKey;
    $_SERVER['RECAPTCHA_SITE_KEY'] = $recaptchaSiteKey;
}
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY');
if ($recaptchaSecret) {
    putenv('RECAPTCHA_SECRET_KEY=' . $recaptchaSecret);
    $_ENV['RECAPTCHA_SECRET_KEY'] = $recaptchaSecret;
    $_SERVER['RECAPTCHA_SECRET_KEY'] = $recaptchaSecret;
}

// reCAPTCHA keys
$recaptchaSiteKey = getenv('RECAPTCHA_SITE_KEY');
if ($recaptchaSiteKey) {
    putenv('RECAPTCHA_SITE_KEY=' . $recaptchaSiteKey);
    $_ENV['RECAPTCHA_SITE_KEY'] = $recaptchaSiteKey;
    $_SERVER['RECAPTCHA_SITE_KEY'] = $recaptchaSiteKey;
}
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY');
if ($recaptchaSecret) {
    putenv('RECAPTCHA_SECRET_KEY=' . $recaptchaSecret);
    $_ENV['RECAPTCHA_SECRET_KEY'] = $recaptchaSecret;
    $_SERVER['RECAPTCHA_SECRET_KEY'] = $recaptchaSecret;
}

// Force bcrypt rounds to 10 to prevent Bcrypt hashing not supported error on Vercel
putenv('BCRYPT_ROUNDS=10');
$_ENV['BCRYPT_ROUNDS'] = '10';
$_SERVER['BCRYPT_ROUNDS'] = '10';

// Force dynamic APP_URL to fix Ziggy route generation
if (isset($_SERVER['HTTP_HOST'])) {
    $appUrl = 'https://' . $_SERVER['HTTP_HOST'];
    putenv('APP_URL=' . $appUrl);
    $_ENV['APP_URL'] = $appUrl;
    $_SERVER['APP_URL'] = $appUrl;
}

// Redirect bootstrap cache to /tmp because Vercel root is read-only
$bootstrapCacheDirs = ['APP_SERVICES_CACHE' => 'services.php', 'APP_PACKAGES_CACHE' => 'packages.php', 'APP_ROUTES_CACHE' => 'routes.php', 'APP_EVENTS_CACHE' => 'events.php'];
foreach ($bootstrapCacheDirs as $envKey => $file) {
    putenv("$envKey=/tmp/bootstrap/cache/$file");
    $_ENV[$envKey] = "/tmp/bootstrap/cache/$file";
    $_SERVER[$envKey] = "/tmp/bootstrap/cache/$file";
}

// Force APP_KEY injection to prevent early boot crashes if missing in Vercel Dashboard
$appKey = 'base64:5YsqYxPWJYwFNajuNIGrofm1+u75dWJ7k8aYcClYP1Q=';
putenv("APP_KEY=$appKey");
$_ENV['APP_KEY'] = $appKey;
$_SERVER['APP_KEY'] = $appKey;

// Create necessary temp directories only if not already initialized
if (!is_dir('/tmp/framework/views')) {
    $tmpDirs = [
        '/tmp/bootstrap/cache',
        '/tmp/framework/cache/data',
        '/tmp/framework/sessions',
        '/tmp/framework/testing',
        '/tmp/framework/views',
        '/tmp/logs',
        '/tmp/views',
        '/tmp/app/public',
        '/tmp/app/public/attachments'
    ];
    foreach ($tmpDirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
    }
}
@unlink('/tmp/bootstrap/cache/routes.php');

try {
    require __DIR__ . '/../vendor/autoload.php';
    
    // Trust all proxies so Laravel knows it's on HTTPS (fixes Secure cookies and sessions)
    \Illuminate\Http\Request::setTrustedProxies(['*'], \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO | \Illuminate\Http\Request::HEADER_X_FORWARDED_AWS_ELB);
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    $app->useStoragePath('/tmp');
    $request = Illuminate\Http\Request::capture();
    $app->handleRequest($request);
} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain');
    echo "FATAL VERCEL ERROR:\n\n";
    while ($e) {
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n\n";
        $e = $e->getPrevious();
    }
}
