<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(Request $request): Response
    {
        // Jika ada request timeout dan user masih terautentikasi, paksa logout
        if ($request->has('timeout') && Auth::check()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $sessionExpiredMsg = null;
        if ($request->has('timeout') || $request->session()->get('session_expired')) {
            $sessionExpiredMsg = $request->session()->get('session_expired')
                ?? 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 5 menit. Silakan login kembali.';
        }

        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status'          => session('status'),
            'sessionExpired'  => $sessionExpiredMsg,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $request->session()->put('last_activity_time', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): mixed
    {
        Auth::guard('web')->logout();

        $request->session()->forget('last_activity_time');
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->has('timeout') || $request->input('timeout')) {
            $request->session()->flash('session_expired', 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 5 menit. Silakan login kembali.');
            if ($request->header('X-Inertia')) {
                return Inertia::location('/?timeout=1');
            }
            return redirect('/?timeout=1');
        }

        if ($request->header('X-Inertia')) {
            return Inertia::location('/');
        }

        return redirect('/');
    }
}
