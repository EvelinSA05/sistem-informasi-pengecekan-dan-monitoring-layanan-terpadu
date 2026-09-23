<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

const props = defineProps({
 email: {
 type: String,
 required: true,
 },
 token: {
 type: String,
 required: true,
 },
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
 token: props.token,
 email: props.email,
 password: '',
 password_confirmation: '',
});

const submit = () => {
 form.post(route('password.store'), {
 onFinish: () => form.reset('password', 'password_confirmation'),
 });
};
</script>

<template>
 <Head title="Atur Ulang Kata Sandi - MAJADIGI CHECKER SYSTEM" />

 <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-primary from-[#1e40af] via-[#1d4ed8] to-[#1e3a8a] relative overflow-hidden font-sans antialiased text-slate-800 selection:bg-primary selection:text-white">
 
 <!-- Ambient Decorative Background Circles & Glow -->
 <div class="absolute inset-0 pointer-events-none overflow-hidden">
 <div class="absolute -left-20 top-10 w-96 h-96 rounded-full border border-white/10 bg-white/[0.02]"></div>
 <div class="absolute -right-20 bottom-0 w-80 h-80 rounded-full border border-white/10 bg-white/[0.02]"></div>
 <div class="absolute right-10 top-20 w-72 h-72 rounded-full bg-cyan-400/15 blur-3xl"></div>
 <div class="absolute left-1/4 bottom-1/4 w-80 h-80 rounded-full bg-primary/20 blur-3xl"></div>
 </div>

 <!-- Floating Card -->
 <div class="relative z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-7 sm:p-9 border border-white/40">
 
 <!-- Logo & Badge Row (Side by side) -->
 <div class="flex items-center justify-center gap-2.5 sm:gap-3 mb-6">
 <div class="px-3.5 py-1.5 rounded-2xl bg-white shadow-md border border-slate-100 flex items-center justify-center">
 <img
 src="/images/logo_majadigi.png"
 alt="Majadigi - Majapahit Digital"
 class="h-6 sm:h-7 w-auto object-contain"
 />
 </div>
 <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary border border-primary shadow-xs shrink-0">
 <span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>
 <span class="text-[10px] font-bold tracking-wider text-primary uppercase">CHECKER SYSTEM</span>
 </div>
 </div>

 <!-- Header -->
 <div class="text-center mb-6">
 <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
 Atur Ulang Kata Sandi
 </h1>
 <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
 Masukkan kata sandi baru untuk akun Checker System Anda.
 </p>
 </div>

 <!-- Form -->
 <form @submit.prevent="submit" class="space-y-4">
 <!-- Email (Readonly or verified) -->
 <div>
 <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
 Alamat Email
 </label>
 <div class="relative">
 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
 </svg>
 </div>
 <input
 id="email"
 type="email"
 v-model="form.email"
 required
 readonly
 class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-slate-100/80 text-sm text-slate-600 focus:outline-none cursor-not-allowed"
 />
 </div>
 <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.email" />
 </div>

 <!-- Password Baru -->
 <div>
 <label for="password" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
 Kata Sandi Baru <span class="text-red-500">*</span>
 </label>
 <div class="relative">
 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
 </svg>
 </div>
 <input
 id="password"
 :type="showPassword ? 'text' : 'password'"
 v-model="form.password"
 required
 placeholder="Minimal 8 karakter"
 autocomplete="new-password"
 class="w-full pl-10 pr-11 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
 :class="{ 'border-red-400 bg-red-50/30 focus:ring-red-400': form.errors.password }"
 />
 <button
 type="button"
 @click="showPassword = !showPassword"
 class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
 >
 <svg v-if="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
 </svg>
 <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
 </svg>
 </button>
 </div>
 <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password" />
 </div>

 <!-- Konfirmasi Kata Sandi -->
 <div>
 <label for="password_confirmation" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
 Ulangi Kata Sandi Baru <span class="text-red-500">*</span>
 </label>
 <div class="relative">
 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
 </svg>
 </div>
 <input
 id="password_confirmation"
 :type="showConfirmPassword ? 'text' : 'password'"
 v-model="form.password_confirmation"
 required
 placeholder="Ulangi kata sandi"
 autocomplete="new-password"
 class="w-full pl-10 pr-11 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
 :class="{ 'border-red-400 bg-red-50/30 focus:ring-red-400': form.errors.password_confirmation }"
 />
 <button
 type="button"
 @click="showConfirmPassword = !showConfirmPassword"
 class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600"
 >
 <svg v-if="!showConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
 </svg>
 <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
 </svg>
 </button>
 </div>
 <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.password_confirmation" />
 </div>

 <!-- Submit Button -->
 <button
 type="submit"
 :disabled="form.processing"
 class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm text-white bg-primary -1 hover:-2 hover:-2 shadow-md shadow-primary/25 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.99] transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed mt-2"
 >
 <svg
 v-if="form.processing"
 class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
 xmlns="http://www.w3.org/2000/svg"
 fill="none"
 viewBox="0 0 24 24"
 >
 <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
 <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
 </svg>
 <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Kata Sandi Baru' }}</span>
 </button>
 </form>

 <!-- Back to Login Link -->
 <div class="mt-6 pt-5 border-t border-slate-100 text-center">
 <Link
 :href="route('login')"
 class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-primary transition-colors"
 >
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
 </svg>
 <span>Kembali ke Halaman Masuk</span>
 </Link>
 </div>
 </div>

 <!-- Footer Copyright -->
 <p class="absolute bottom-4 text-center w-full text-xs text-primary/75 z-10 px-4">
 © 2026 Dinas Komunikasi dan Informatika Provinsi Jawa Timur.
 </p>
 </div>
</template>
