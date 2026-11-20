<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 - Akses Ditolak | Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/icon-simonter.svg') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    @vite(['resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-900 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden text-center relative">
        <div class="h-32 bg-emerald-600 relative overflow-hidden flex flex-col items-center justify-center pb-8">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <h1 class="text-5xl font-black text-white relative z-10 drop-shadow-md tracking-tight">403</h1>
            <p class="text-emerald-100 font-semibold relative z-10 tracking-widest uppercase text-xs mt-1">Forbidden Access</p>
        </div>
        
        <div class="p-8 pb-10">
            <div class="w-16 h-16 bg-rose-100 text-rose-500 rounded-2xl flex items-center justify-center mx-auto -mt-16 mb-4 relative z-20 shadow-sm border-4 border-white">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            
            <h2 class="text-xl font-bold text-slate-800 mb-2">{{ $exception->getMessage() ?: 'Akses Ditolak' }}</h2>
            <p class="text-slate-500 text-sm leading-relaxed mb-4">
                Maaf, Anda tidak memiliki hak akses (izin) yang cukup untuk membuka halaman ini.
            </p>
            
            <div class="mt-5 text-xs text-slate-400">
                &copy; {{ date('Y') }} Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu
            </div>
        </div>
    </div>
</body>
</html>
