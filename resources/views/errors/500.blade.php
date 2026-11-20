<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>500 - Terjadi Kesalahan | SIMONTER</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-simonter.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden text-center relative">
        <div class="h-32 bg-slate-800 relative overflow-hidden flex flex-col items-center justify-center pb-8">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <h1 class="text-5xl font-black text-white relative z-10 drop-shadow-md tracking-tight">500</h1>
            <p class="text-slate-300 font-semibold relative z-10 tracking-widest uppercase text-xs mt-1">Server Error</p>
        </div>
        
        <div class="p-8 pb-10">
            <div class="w-16 h-16 bg-rose-100 text-rose-500 rounded-2xl flex items-center justify-center mx-auto -mt-16 mb-4 relative z-20 shadow-sm border-4 border-white">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-slate-800 mb-2">Terjadi Kesalahan</h2>
            <p class="text-slate-500 text-sm leading-relaxed mb-6">
                {{ $message ?? 'Maaf, terjadi kesalahan saat memproses permintaan Anda. Silakan periksa kembali input Anda atau coba beberapa saat lagi.' }}
            </p>
            @if(config('app.debug') && isset($exception) && $exception)
                <div style="background: #fff1f2; color: #b91c1c; padding: 12px; border-radius: 8px; font-family: monospace; font-size: 11px; text-align: left; margin-bottom: 16px; word-break: break-all;">
                    <strong>Error:</strong> {{ $exception->getMessage() }}<br>
                    <strong>File:</strong> {{ $exception->getFile() }}:{{ $exception->getLine() }}
                </div>
            @endif

            <a href="{{ url('/') }}" class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-md shadow-emerald-600/20 transition-all">
                Kembali ke Beranda
            </a>
            
            <div class="mt-6 text-xs text-slate-400">
                &copy; {{ date('Y') }} SIMONTER — Sistem Monitoring &amp; Checking Layanan Terpadu
            </div>
        </div>
    </div>
</body>
</html>
