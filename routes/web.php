<?php

use App\Http\Controllers\Admin\PeriodController as AdminPeriodController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
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

        // Periode Pengecekan
        Route::get('/periods', [AdminPeriodController::class, 'index'])->name('periods.index');
        Route::get('/periods/create', [AdminPeriodController::class, 'create'])->name('periods.create');
        Route::post('/periods', [AdminPeriodController::class, 'store'])->name('periods.store');
        Route::get('/periods/{period}', [AdminPeriodController::class, 'show'])->name('periods.show');
        Route::put('/periods/{period}', [AdminPeriodController::class, 'update'])->name('periods.update');
        Route::delete('/periods/{period}', [AdminPeriodController::class, 'destroy'])->name('periods.destroy');
    });
});

require __DIR__.'/auth.php';
