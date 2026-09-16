<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const showPassword = ref(false);

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="MAJADIGI CHECKER SYSTEM - Pantau Layanan Digital Jawa Timur" />

    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-[#f8fafc] selection:bg-blue-600 selection:text-white font-sans antialiased">
        <!-- Left Side (Desktop) / Top Banner Header (Mobile) -->
        <div class="relative w-full lg:w-[48%] xl:w-[45%] bg-[#1d4ed8] bg-gradient-to-b from-[#1e40af] via-[#1d4ed8] to-[#2563eb] p-6 sm:p-8 lg:p-14 text-white flex flex-col justify-between overflow-hidden lg:min-h-screen shrink-0">
            
            <!-- Ambient Background Decorative Circles -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -left-20 top-10 w-96 h-96 rounded-full border border-white/10 bg-white/[0.02]"></div>
                <div class="absolute -right-20 bottom-0 w-80 h-80 rounded-full border border-white/10 bg-white/[0.02]"></div>
                <div class="absolute right-10 top-20 w-64 h-64 rounded-full bg-cyan-400/10 blur-2xl"></div>
            </div>

            <!-- Top Header: Logo Card & Checker System Pill -->
            <div class="relative z-10 flex items-center justify-between gap-3">
                <!-- Logo Card -->
                <div class="px-3.5 py-1.5 rounded-xl bg-white shadow-md border border-white/80 flex items-center gap-2">
                    <img
                        src="/images/logo_majadigi.png"
                        alt="Majadigi - Majapahit Digital"
                        class="h-7 sm:h-8 w-auto object-contain"
                    />
                </div>

                <!-- Sub-title Badge -->
                <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span class="text-[11px] font-bold tracking-wider text-white uppercase">CHECKER SYSTEM</span>
                </div>
            </div>

            <!-- Middle Headline & Description -->
            <div class="relative z-10 my-6 lg:my-auto max-w-lg">
                <h1 class="text-2xl sm:text-3xl lg:text-4xl xl:text-5xl font-extrabold text-white tracking-tight leading-tight">
                    Pantau Layanan Digital<br/>
                    <span class="text-white">Jawa Timur</span>
                </h1>

                <p class="mt-2.5 sm:mt-3 text-blue-100/90 text-xs sm:text-sm lg:text-base font-normal leading-relaxed">
                    Platform monitoring terpusat untuk mengecek dan memantau kualitas layanan Majadigi secara berkala dan terstruktur.
                </p>

                <!-- Features Cards (2 Kolom berdampingan persis seperti di gambar) -->
                <div class="grid grid-cols-2 gap-2.5 sm:gap-3 mt-4 sm:mt-6">
                    <!-- Feature 1: Dashboard Real-time -->
                    <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20">
                        <div class="w-8 h-8 shrink-0 rounded-xl bg-white/20 flex items-center justify-center text-white">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] sm:text-xs font-bold text-white leading-tight truncate">Dashboard Real-time</p>
                            <p class="text-[9px] sm:text-[10px] text-blue-100/80 truncate">Pantau seluruh layanan</p>
                        </div>
                    </div>

                    <!-- Feature 2: Distribusi Otomatis -->
                    <div class="flex items-center gap-2.5 p-2.5 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20">
                        <div class="w-8 h-8 shrink-0 rounded-xl bg-white/20 flex items-center justify-center text-white">
                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[11px] sm:text-xs font-bold text-white leading-tight truncate">Distribusi Otomatis</p>
                            <p class="text-[9px] sm:text-[10px] text-blue-100/80 truncate">Cerdas & merata</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Desktop Bottom Footer text / subtle info -->
            <div class="relative z-10 hidden lg:block pt-4">
                <p class="text-xs text-blue-200/70">© Majadigi Checking Layanan · Pemerintah Provinsi Jawa Timur</p>
            </div>
        </div>

        <!-- Right Side (Desktop) / Bottom Card Sheet (Mobile): Form Login Area -->
        <div class="flex-1 flex flex-col justify-center items-center bg-white -mt-6 lg:mt-0 rounded-t-[32px] lg:rounded-none px-6 py-6 sm:px-10 lg:px-14 xl:px-20 z-20 shadow-2xl lg:shadow-none min-h-0">
            <div class="w-full max-w-[420px] my-auto">
                
                <!-- Bottom Sheet Handle Bar (khusus mobile) -->
                <div class="flex justify-center mb-4 lg:hidden">
                    <div class="w-12 h-1.5 rounded-full bg-slate-200"></div>
                </div>

                <!-- Form Header -->
                <div class="mb-5 sm:mb-6">
                    <h2 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
                        Masuk ke Checker System
                    </h2>
                    <p class="text-slate-500 text-xs sm:text-sm mt-1.5 leading-relaxed">
                        Validasi keandalan dan kualitas layanan publik Majadigi secara berkala.
                    </p>
                </div>

                <!-- Status Flash Message -->
                <div v-if="status" class="mb-4 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs sm:text-sm font-medium text-emerald-700 flex items-center gap-2">
                    <svg class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>{{ status }}</span>
                </div>

                <!-- Form Inputs -->
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                            Email
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="4"/>
                                    <path d="M16 8v5a3 3 0 006 0v-1a10 10 0 10-3.92 7.94"/>
                                </svg>
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="budi@gmail.com"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200 shadow-sm"
                            />
                        </div>
                        <InputError class="mt-1" :message="form.errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0110 0v4"/>
                                </svg>
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="Masukkan password"
                                required
                                autocomplete="current-password"
                                class="w-full pl-10 pr-10 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 text-xs sm:text-sm focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200 shadow-sm"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                                :title="showPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                            >
                                <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-1" :message="form.errors.password" />
                    </div>

                    <!-- Ingat saya & Lupa Password -->
                    <div class="flex items-center justify-between text-xs sm:text-sm">
                        <label class="flex items-center gap-2 cursor-pointer select-none text-slate-600">
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 w-4 h-4"
                            />
                            <span>Ingat saya</span>
                        </label>
                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                        >
                            Lupa Password?
                        </Link>
                    </div>

                    <!-- Action Buttons (Masuk + QR/Biometric button icon) -->
                    <div class="flex items-center gap-2.5 pt-1">
                        <button
                            type="submit"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                            class="flex-1 py-3 px-6 rounded-xl bg-[#2563eb] hover:bg-[#1d4ed8] active:bg-blue-800 text-white font-semibold text-sm sm:text-base flex items-center justify-center gap-2 shadow-lg shadow-blue-600/25 transition-all duration-200 active:scale-[0.99]"
                        >
                            <span>Masuk</span>
                        </button>
                        <button
                            type="button"
                            title="Pindai QR / Autentikasi"
                            class="w-12 h-12 shrink-0 rounded-xl border border-slate-200 bg-slate-50 hover:bg-slate-100 active:bg-slate-200 text-slate-600 flex items-center justify-center transition-colors shadow-sm"
                        >
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 7V5a2 2 0 0 1 2-2h2"/>
                                <path d="M17 3h2a2 2 0 0 1 2 2v2"/>
                                <path d="M21 17v2a2 2 0 0 1-2 2h-2"/>
                                <path d="M7 21H5a2 2 0 0 1-2-2v-2"/>
                                <circle cx="12" cy="12" r="2"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Bottom Link to Register -->
                    <div class="text-center pt-3 border-t border-slate-100">
                        <p class="text-xs sm:text-sm text-slate-500">
                            Belum memiliki akun?
                            <Link :href="route('register')" class="font-semibold text-blue-600 hover:text-blue-700 hover:underline ml-1">
                                Daftar Sekarang
                            </Link>
                        </p>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>
