<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            // Flash messages dibagikan ke semua halaman Inertia secara otomatis
            'flash' => [
                'session_expired' => $request->session()->get('session_expired'),
                'status'          => $request->session()->get('status'),
                'error'           => $request->session()->get('error'),
                'success'         => $request->session()->get('success'),
            ],
            'recaptcha_site_key' => config('services.recaptcha.site_key'),
        ];
    }
}
