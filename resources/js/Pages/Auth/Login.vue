<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
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
    <Head title="Masuk - MAJADIGI CHECKER SYSTEM" />

    <div class="min-h-screen w-full flex flex-col lg:flex-row bg-[#f8fafc] selection:bg-blue-600 selection:text-white font-sans antialiased">
        <!-- Left Side: Hero & Branding Banner -->
        <div class="relative w-full lg:w-[48%] xl:w-[45%] bg-gradient-to-br from-[#1d4ed8] via-[#1e40af] to-[#1e3a8a] p-8 sm:p-12 lg:p-14 text-white flex flex-col justify-between overflow-hidden min-h-[560px] lg:min-h-screen">
            
            <!-- Ambient Background Decorative Concentric Circles -->
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <!-- Large decorative circle 1 -->
                <div class="absolute -left-24 top-1/4 w-[520px] h-[520px] rounded-full border border-white/10 bg-white/[0.02]"></div>
                <!-- Large decorative circle 2 -->
                <div class="absolute -left-12 top-1/3 w-[680px] h-[680px] rounded-full border border-white/10 bg-white/[0.01]"></div>
                <!-- Middle radial glow -->
                <div class="absolute left-1/4 top-1/2 -translate-y-1/2 w-[420px] h-[420px] rounded-full bg-blue-400/10 blur-3xl"></div>
                <!-- Bottom subtle ring -->
                <div class="absolute -right-24 bottom-10 w-[380px] h-[380px] rounded-full border border-white/10 bg-white/[0.015]"></div>
            </div>

            <!-- Top Header / Logo -->
            <div class="relative z-10 flex items-center gap-3.5">
                <div class="w-11 h-11 rounded-xl bg-white/15 backdrop-blur-md border border-white/25 flex items-center justify-center shadow-inner text-white">
                    <!-- Shield Checker Icon -->
                    <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L4 5V11.09C4 16.14 7.41 20.85 12 22C16.59 20.85 20 16.14 20 11.09V5L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="currentColor" fill-opacity="0.15"/>
                        <path d="M9 11.5L11 13.5L15 9.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold tracking-wider text-white leading-tight">MAJADIGI</h1>
                    <p class="text-[11px] font-bold tracking-widest text-cyan-300 uppercase">CHECKER SYSTEM</p>
                </div>
            </div>

            <!-- Middle Headline & Description -->
            <div class="relative z-10 my-auto py-10 lg:py-0 max-w-lg">
                <h2 class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight leading-[1.15]">
                    Pantau Layanan<br/>
                    Digital<br/>
                    <span class="text-cyan-300 drop-shadow-sm">Jawa Timur</span>
                </h2>

                <p class="mt-5 text-blue-100/85 text-sm sm:text-base font-normal leading-relaxed">
                    Platform monitoring terpusat untuk mengecek dan memantau kualitas layanan Majadigi secara berkala dan terstruktur.
                </p>
            </div>

            <!-- Bottom Features List -->
            <div class="relative z-10 space-y-4 pt-6">
                <!-- Feature 1: Dashboard Real-time -->
                <div class="flex items-center gap-3.5 group">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shadow-sm transition-transform duration-300 group-hover:scale-105">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="18" height="18" rx="2" stroke-opacity="0.5" />
                            <path d="M8 17V12" stroke="#38bdf8" />
                            <path d="M12 17V8" stroke="#ffffff" />
                            <path d="M16 17V14" stroke="#38bdf8" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white leading-snug">Dashboard Real-time</h3>
                        <p class="text-xs text-blue-100/75">Pantau status seluruh layanan dalam satu tampilan</p>
                    </div>
                </div>

                <!-- Feature 2: Distribusi Otomatis -->
                <div class="flex items-center gap-3.5 group">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shadow-sm transition-transform duration-300 group-hover:scale-105">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 3h5v5" />
                            <path d="M4 20L21 3" />
                            <path d="M21 16v5h-5" />
                            <path d="M15 15l6 6" />
                            <path d="M4 4l5 5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white leading-snug">Distribusi Otomatis</h3>
                        <p class="text-xs text-blue-100/75">Layanan dibagi merata ke semua tester secara cerdas</p>
                    </div>
                </div>

                <!-- Feature 3: Laporan Lengkap -->
                <div class="flex items-center gap-3.5 group">
                    <div class="w-10 h-10 shrink-0 rounded-xl bg-white/15 backdrop-blur-md border border-white/20 flex items-center justify-center text-white shadow-sm transition-transform duration-300 group-hover:scale-105">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                            <polyline points="14 2 14 8 20 8" />
                            <line x1="16" y1="13" x2="8" y2="13" />
                            <line x1="16" y1="17" x2="8" y2="17" />
                            <polyline points="10 9 9 9 8 9" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-semibold text-white leading-snug">Laporan Lengkap</h3>
                        <p class="text-xs text-blue-100/75">Ekspor hasil pengecekan ke PDF dan Excel</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Side: Authentication Form Area -->
        <div class="w-full lg:w-[52%] xl:w-[55%] flex items-center justify-center p-6 sm:p-12 lg:p-16 bg-[#ffffff]">
            <div class="w-full max-w-[440px] py-4">
                
                <!-- Form Header -->
                <div class="mb-8">
                    <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight flex items-center gap-2">
                        <span>Selamat Datang</span>
                        <span class="inline-block transform hover:rotate-12 transition-transform duration-200 cursor-default">👋</span>
                    </h2>
                    <p class="text-slate-500 text-sm sm:text-base mt-2">
                        Masuk dengan akun Anda untuk melanjutkan
                    </p>
                </div>

                <div v-if="status" class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-sm font-medium text-emerald-700">
                    {{ status }}
                </div>

                <!-- Form Inputs -->
                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Field -->
                    <div>
                        <label for="email" class="block text-sm font-semibold text-slate-700 mb-2">
                            Email
                        </label>
                        <div class="relative">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="budi@kominfo.jatim.go.id"
                                required
                                autofocus
                                autocomplete="username"
                                class="w-full px-4 py-3.5 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 text-sm focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200 shadow-sm"
                            />
                        </div>
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <!-- Password Field -->
                    <div>
                        <label for="password" class="block text-sm font-semibold text-slate-700 mb-2">
                            Kata Sandi
                        </label>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••••••"
                                required
                                autocomplete="current-password"
                                class="w-full px-4 py-3.5 pr-11 rounded-xl border border-slate-200 bg-white text-slate-800 placeholder-slate-400 text-sm focus:outline-none focus:border-blue-600 focus:ring-4 focus:ring-blue-500/10 transition-all duration-200 shadow-sm"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none transition-colors p-1"
                                :title="showPassword ? 'Sembunyikan sandi' : 'Tampilkan sandi'"
                            >
                                <svg v-if="!showPassword" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                </svg>
                            </button>
                        </div>
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :class="{ 'opacity-50 pointer-events-none': form.processing }"
                            :disabled="form.processing"
                            class="w-full py-3.5 px-6 rounded-xl bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white font-semibold text-sm sm:text-base flex items-center justify-center gap-2 shadow-lg shadow-blue-600/25 hover:shadow-blue-600/35 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
                        >
                            <span>Masuk</span>
                            <span class="text-lg leading-none font-normal">→</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</template>
