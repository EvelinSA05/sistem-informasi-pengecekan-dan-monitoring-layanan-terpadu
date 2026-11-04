<?php

use App\Http\Controllers\Admin\PeriodController as AdminPeriodController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // ─── Tester Routes ─────────────────────────────────────────
    Route::get('/tester/services', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'index'])->name('tester.services.index');
    Route::get('/tester/periods', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'index'])->name('tester.periods.index');
    Route::get('/tester/periods/{period}', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'showPeriod'])->name('tester.periods.show');
    Route::get('/tester/periods/{period}/services', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'showPeriod'])->name('tester.periods.services');
    Route::get('/tester/assigned-services', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'index'])->name('tester.assigned-services.index');
    Route::post('/tester/check/{periodService}', [\App\Http\Controllers\Tester\AssignedServiceController::class, 'submitCheck'])->name('tester.submit_check');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Admin Routes ──────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware([\App\Http\Middleware\IsAdmin::class])->group(function () {
        // Manajemen User
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Master Layanan
        Route::get('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'store'])->name('services.store');
        Route::post('/services/sync-majadigi', [\App\Http\Controllers\Admin\ServiceController::class, 'syncMajadigi'])->name('services.sync_majadigi');
        Route::get('/services/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'show'])->name('services.show');
        Route::get('/services/{service}/history', [\App\Http\Controllers\Admin\ServiceController::class, 'history'])->name('services.history');
        Route::get('/services/{service}/issues', [\App\Http\Controllers\Admin\ServiceController::class, 'issues'])->name('services.issues');
        Route::post('/services/issues/{issue}/toggle-solved', [\App\Http\Controllers\Admin\ServiceController::class, 'toggleIssueSolved'])->name('services.issues.toggle_solved');
        Route::put('/services/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroy'])->name('services.destroy');

        // Periode Pengecekan
        Route::get('/periods', [AdminPeriodController::class, 'index'])->name('periods.index');
        Route::get('/periods/create', [AdminPeriodController::class, 'create'])->name('periods.create');
        Route::post('/periods', [AdminPeriodController::class, 'store'])->name('periods.store');
        Route::get('/periods/{period}', [AdminPeriodController::class, 'show'])->name('periods.show');
        Route::get('/periods/{period}/edit', [AdminPeriodController::class, 'edit'])->name('periods.edit');
        Route::put('/periods/{period}', [AdminPeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [AdminPeriodController::class, 'destroy'])->name('periods.destroy');
        Route::get('/periods/{period}/export/excel', [AdminPeriodController::class, 'exportExcel'])->name('periods.export.excel');
        Route::get('/periods/{period}/export/pdf', [AdminPeriodController::class, 'exportPdf'])->name('periods.export.pdf');
        Route::post('/periods/{period}/sync-services', [AdminPeriodController::class, 'syncPeriodServices'])->name('periods.sync_services');
        Route::get('/periods/attachment/{periodService}/download', [AdminPeriodController::class, 'downloadAttachment'])->name('periods.attachment.download');
    });
});

Route::get('/run-migrations', function (\App\Services\MajadigiApiService $apiService) {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    $syncMsg = '';
    try {
        $result = $apiService->syncServices();
        $syncMsg = " Sinkronisasi Majadigi otomatis: {$result['total']} layanan diproses ({$result['deleted']} data non-sinkronisasi dibersihkan).";
    } catch (\Throwable $e) {
        \Illuminate\Support\Facades\Log::warning('Run-migrations syncMajadigi: ' . $e->getMessage());
        $syncMsg = " (Catatan sinkronisasi otomatis: " . $e->getMessage() . ")";
    }
    $total = \App\Models\Service::whereNotNull('synced_at')->count();
    return "Migrations and cleanup ran successfully! Total layanan aktif tersinkronisasi dari Majadigi: {$total}.{$syncMsg}";
});

Route::get('/test-session', function (\Illuminate\Http\Request $request) {
    $request->session()->put('test_key', 'This is a test session value!');
    setcookie('raw_test', 'Works!', time() + 3600, '/', '', true, true);
    return 'Session set! Now go to /check-session';
});

Route::get('/check-session', function (\Illuminate\Http\Request $request) {
    $count = \Illuminate\Support\Facades\DB::table('sessions')->count();
    $rawCookie = $_COOKIE['raw_test'] ?? 'MISSING';
    
    $cookieName = config('session.cookie');
    $laravelCookie = $_COOKIE[$cookieName] ?? 'MISSING';
    
    $appKey = config('app.key');
    
    return "Session: " . $request->session()->get('test_key', 'NOT FOUND') . 
           " | RawCookie: " . $rawCookie . 
           " | LaravelCookie: " . substr($laravelCookie, 0, 15) . "... " .
           " | APP_KEY: " . $appKey .
           " | DB: " . $count;
});

require __DIR__.'/auth.php';
