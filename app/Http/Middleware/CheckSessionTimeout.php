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
            $user = Auth::user();

            // Jika akun dinonaktifkan saat sesi sedang aktif, segera keluarkan user
            if ($user && $user->status === 'tidak aktif') {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                $request->session()->flash('session_expired', 'Akun Anda telah dinonaktifkan oleh Administrator.');

                if ($request->header('X-Inertia')) {
                    return Inertia::location('/');
                }

                return redirect('/');
            }

            // Jika user memilih "Ingat Saya" (punya cookie remember_web), kita lewati fitur auto-logout 1 jam ini
            $recallerName = Auth::guard('web')->getRecallerName();
            if ($request->hasCookie($recallerName)) {
                return $next($request);
            }

            $role = $user?->role;

            // Session timeout 1 jam (60 menit = 3600 detik) untuk masing-masing role (Admin maupun Tester)
            $timeoutSeconds = match ($role) {
                'admin'  => 60 * 60,
                'tester' => 60 * 60,
                default  => 60 * 60,
            };

            $lastActivity = $request->session()->get('last_activity_time');

            if ($lastActivity && (time() - $lastActivity > $timeoutSeconds)) {
                $message = 'Sesi Anda telah berakhir karena tidak ada aktivitas selama 1 jam. Silakan login kembali.';

                // Logout dan invalidate session
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Simpan flash message ke session baru setelah invalidate
                $request->session()->flash('session_expired', $message);

                // Inertia XHR request membutuhkan Inertia::location() untuk memicu
                // full-page redirect agar browser bisa mengikuti redirect dengan benar
                if ($request->header('X-Inertia')) {
                    return Inertia::location('/?timeout=1');
                }

                return redirect('/?timeout=1');
            }

            // User aktif: jangan perbarui timestamp jika ini hanya background poll (usePoll)
            if (! $request->header('X-Inertia-Partial-Data')) {
                $request->session()->put('last_activity_time', time());
            }
        }

        return $next($request);
    }
}
