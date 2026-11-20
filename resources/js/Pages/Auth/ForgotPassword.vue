<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import InputError from '@/Components/InputError.vue';

defineProps({
 status: {
 type: String,
 },
});

const form = useForm({
 email: '',
});

const submit = () => {
 form.post(route('password.email'));
};
</script>

<template>
 <Head title="Lupa Kata Sandi - Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu" />

 <div class="min-h-screen w-full flex items-center justify-center p-4 sm:p-6 bg-gradient-to-br from-emerald-700 via-emerald-600 to-teal-700 relative overflow-hidden font-sans antialiased text-slate-800 selection:bg-primary selection:text-white">
 
 <!-- Ambient Decorative Background Circles & Glow -->
 <div class="absolute inset-0 pointer-events-none overflow-hidden">
 <div class="absolute -left-20 top-10 w-96 h-96 rounded-full border border-white/10 bg-white/[0.02]"></div>
 <div class="absolute -right-20 bottom-0 w-80 h-80 rounded-full border border-white/10 bg-white/[0.02]"></div>
 <div class="absolute right-10 top-20 w-72 h-72 rounded-full bg-emerald-300/20 blur-3xl"></div>
 <div class="absolute left-1/4 bottom-1/4 w-80 h-80 rounded-full bg-teal-400/20 blur-3xl"></div>
 </div>

 <!-- Floating Card -->
 <div class="relative z-10 w-full max-w-md bg-white rounded-3xl shadow-2xl p-7 sm:p-9 border border-white/40">
 
 <!-- Logo & Badge Row (Side by side) -->
 <div class="flex items-center justify-center gap-2.5 sm:gap-3 mb-6">
			<div class="px-3.5 py-2 rounded-2xl bg-white shadow-md border border-slate-100 flex items-center gap-2.5">
				<img
					src="/images/icon-layanan-terpadu.svg"
					alt="Icon"
					class="w-7 h-7 shrink-0 object-contain"
				/>
				<div class="flex flex-col text-left">
					<span class="text-[11px] font-extrabold text-[#064e3b] leading-tight tracking-tight uppercase">SISTEM INFORMASI</span>
					<span class="text-[8.5px] font-bold text-[#059669] leading-tight tracking-tight uppercase">PENGECEKAN &amp; MONITORING</span>
				</div>
			</div>
		</div>

		<!-- Header -->
 <div class="text-center mb-6">
 <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">
 Lupa Kata Sandi?
 </h1>
 <p class="text-xs sm:text-sm text-slate-500 mt-1.5 leading-relaxed">
 Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk mengatur ulang kata sandi.
 </p>
 </div>

 <!-- Status Alert (Success Sent) -->
 <div
 v-if="status"
 class="mb-5 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-xs sm:text-sm font-medium text-emerald-800 flex items-start gap-2.5"
 >
 <svg class="w-5 h-5 text-emerald-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
 </svg>
 <span>{{ status }}</span>
 </div>

 <!-- Form -->
 <form @submit.prevent="submit" class="space-y-4">
 <div>
 <label for="email" class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1.5">
 Alamat Email <span class="text-red-500">*</span>
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
 autofocus
 autocomplete="email"
 placeholder="nama@email.com"
 class="w-full pl-10 pr-4 py-2.5 sm:py-3 rounded-xl border border-slate-200 bg-slate-50/50 text-sm text-slate-800 placeholder:text-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-primary focus:border-transparent transition-all duration-200"
 :class="{ 'border-red-400 bg-red-50/30 focus:ring-red-400': form.errors.email }"
 />
 </div>
 <InputError class="mt-1.5 text-xs text-red-500" :message="form.errors.email" />
 </div>

 <!-- Submit Button -->
 <button
 type="submit"
 :disabled="form.processing"
 class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm text-white bg-primary hover:bg-primary/90 shadow-md shadow-primary/25 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.99] transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
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
 <span>{{ form.processing ? 'Mengirim...' : 'Kirim Tautan Reset Kata Sandi' }}</span>
 </button>
 </form>

 <!-- Back to Login Link -->
 <div class="mt-3 text-center">
 <Link
 :href="route('login')"
 class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl border border-slate-200 bg-slate-50/80 text-xs sm:text-sm font-bold text-slate-600 hover:bg-slate-100 hover:text-primary hover:border-slate-300 transition-all duration-150"
 >
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
 </svg>
 <span>Kembali ke Halaman Masuk</span>
 </Link>
 </div>
 </div>

 <!-- Footer Copyright -->
 <p class="absolute bottom-4 text-center w-full text-xs text-white/80 z-10 px-4">
 © 2026 Sistem Informasi Pengecekan dan Monitoring Layanan Terpadu.
 </p>
 </div>
</template>
