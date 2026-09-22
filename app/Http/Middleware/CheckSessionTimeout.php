<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckSessionTimeout
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Hanya periksa jika user sedang terautentikasi (Admin maupun Tester)
        if (Auth::check()) {
            $lastActivity = $request->session()->get('last_activity_time');
            // Timeout dalam detik (default 5 menit = 300 detik sesuai config('session.lifetime') * 60)
            $timeoutSeconds = (int) config('session.lifetime', 5) * 60;

            if ($lastActivity && (time() - $lastActivity > $timeoutSeconds)) {
                $message = 'Sesi Anda telah berakhir karena tidak ada aktivitas. Silakan login kembali.';

                // Logout dan invalidate session
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Simpan flash message ke session baru setelah invalidate
                $request->session()->flash('session_expired', $message);

                // Inertia XHR request membutuhkan Inertia::location() untuk memicu
                // full-page redirect agar browser bisa mengikuti redirect dengan benar
                // dan flash message tidak hilang karena Inertia SPA navigation.
                if ($request->header('X-Inertia')) {
                    return Inertia::location(route('login'));
                }

                return redirect()->route('login');
            }

            // User aktif: perbarui timestamp aktivitas terakhir
            $request->session()->put('last_activity_time', time());
        }

        return $next($request);
    }
}
