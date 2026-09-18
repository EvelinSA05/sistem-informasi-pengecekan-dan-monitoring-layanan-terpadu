<?php

use App\Http\Controllers\Admin\PeriodController as AdminPeriodController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;


Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::post('/tester/check/{periodService}', [\App\Http\Controllers\DashboardController::class, 'submitCheck'])->name('tester.submit_check');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Admin Routes ──────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->group(function () {
        // Manajemen User
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

        // Master Layanan
        Route::get('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('services.index');
        Route::post('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'store'])->name('services.store');
        Route::get('/services/{service}/history', [\App\Http\Controllers\Admin\ServiceController::class, 'history'])->name('services.history');
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
    });
});

Route::get('/run-migrations', function () {
    \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
    \Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
    return 'Migrations and seeders ran successfully! You can now go back and login.';
});

require __DIR__.'/auth.php';
