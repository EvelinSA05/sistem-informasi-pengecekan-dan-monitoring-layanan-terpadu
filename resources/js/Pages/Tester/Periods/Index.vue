<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, usePoll } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
 periods: { type: Array, default: () => [] },
 flash: { type: Object, default: () => ({}) },
});

// ─── Realtime Polling (Sinkronisasi Status Periode Otomatis) ──────
// Memperbarui daftar periode secara otomatis setiap 2 detik tanpa reload halaman
usePoll(2000, {
 only: ['periods'],
});

// ─── Flash Notification ──────────────────────────────────────────
const flashMsg = ref(props.flash?.success || props.flash?.error || '');
const flashType = ref(props.flash?.error ? 'error' : 'success');
let flashTimer = null;
const progressWidth = ref(100);
const flashDuration = ref(4000);

function clearFlashTimer() {
 if (flashTimer) {
 clearTimeout(flashTimer);
 flashTimer = null;
 }
}

function closeFlash() {
 clearFlashTimer();
 flashMsg.value = '';
}

function showFlash(msg, type = 'success', duration = 4000) {
 clearFlashTimer();
 flashMsg.value = msg;
 flashType.value = type;
 flashDuration.value = duration;
 
 progressWidth.value = 100;
 setTimeout(() => {
 if (flashMsg.value === msg) {
 progressWidth.value = 0;
 }
 }, 50);

 flashTimer = setTimeout(() => {
 flashMsg.value = '';
 flashTimer = null;
 }, duration);
}

// Watch props.flash if updated via Inertia visit / redirect
watch(() => props.flash, (newFlash) => {
 if (newFlash?.success) {
 showFlash(newFlash.success, 'success', 4000);
 } else if (newFlash?.error) {
 showFlash(newFlash.error, 'error', 4000);
 }
}, { deep: true });

onMounted(() => {
 if (flashMsg.value) {
 flashDuration.value = 4000;
 progressWidth.value = 100;
 setTimeout(() => {
 progressWidth.value = 0;
 }, 50);

 flashTimer = setTimeout(() => {
 flashMsg.value = '';
 flashTimer = null;
 }, 4000);
 }
});

onUnmounted(() => {
 clearFlashTimer();
});

</script>

<template>
 <Head title="Layanan Ditugaskan - MAJADIGI CHECKER" />

 <AdminLayout
 title="Layanan Ditugaskan"
 subtitle="Daftar periode pengujian layanan yang ditugaskan kepada Anda"
 >
 <!-- ── Flash Notification ──────────────────────────────── -->
 <Transition
 enter-active-class="transition-all duration-300 ease-out"
 enter-from-class="opacity-0 translate-y-4"
 enter-to-class="opacity-100 translate-y-0"
 leave-active-class="transition-all duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0"
 leave-to-class="opacity-0 translate-y-4"
 >
 <div
 v-if="flashMsg"
 :class="[
 'fixed bottom-[24px] right-[24px] z-[100] flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-lg text-sm font-semibold border backdrop-blur-sm transition-all max-w-md overflow-hidden',
 flashType === 'success'
 ? 'bg-emerald-50/95 border-emerald-200 text-emerald-800 shadow-emerald-900/10'
 : 'bg-red-50/95 border-red-200 text-red-800 shadow-red-900/10'
 ]"
 >
 <div class="flex items-center gap-2.5 relative z-10">
 <svg v-if="flashType === 'success'" class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 <svg v-else class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
 </svg>
 <span>{{ flashMsg }}</span>
 </div>

 <!-- Close Button -->
 <button
 type="button"
 @click="closeFlash"
 class="p-1 rounded-xl transition-colors focus:outline-none shrink-0 relative z-10"
 :class="flashType === 'success'
 ? 'text-emerald-500 hover:text-emerald-800 hover:bg-emerald-100/70'
 : 'text-red-500 hover:text-red-800 hover:bg-red-100/70'"
 aria-label="Tutup notifikasi"
 title="Tutup"
 >
 <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
 </svg>
 </button>

 <!-- Progress Bar -->
 <div
 class="absolute bottom-0 left-0 h-1 transition-all ease-linear"
 :class="flashType === 'success' ? 'bg-emerald-500/40' : 'bg-red-500/40'"
 :style="{
 width: progressWidth + '%',
 transitionDuration: progressWidth === 100 ? '0ms' : `${flashDuration}ms`
 }"
 ></div>
 </div>
 </Transition>

 <!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          Penugasan Pengecekan
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Daftar Periode Pengecekan</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Pilih periode aktif di bawah ini untuk memulai atau melanjutkan proses pengujian layanan digital.</p>
      </div>
    </div>

    <!-- ── Main Card Container ──────────────────────────────── -->
 <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

 <!-- Card Header / Toolbar (Tester View - No Create Button) -->
 <div class="px-6 py-4 flex items-center justify-between gap-3 border-b border-slate-100">
 <h2 class="text-sm font-bold text-slate-800 truncate">
 {{ periods.length }} Periode terdaftar
 </h2>
 <div class="text-xs text-slate-400 font-medium hidden sm:block">
 Pilih periode untuk melihat atau melakukan pengecekan layanan
 </div>
 </div>

 <!-- Table -->
 <div class="overflow-x-auto">
 <table class="w-full text-sm min-w-[620px]">
 <thead>
 <tr class="border-b border-slate-100 bg-slate-50/50">
 <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Periode</th>
 <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
 <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Progress</th>
 <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100">
 <tr
 v-for="period in periods"
 :key="period.id"
 :class="[
 'transition-colors group',
 period.status === 'proses'
 ? 'hover:bg-slate-50/70 bg-white'
 : 'hover:bg-slate-100/60 bg-slate-50/40 opacity-75'
 ]"
 >
 <!-- Periode -->
 <td class="px-5 py-3.5 text-left">
 <Link
 :href="route('tester.periods.services', period.id)"
 class="inline-block text-left group-hover:text-primary transition-colors"
 >
 <p class="font-semibold text-slate-800 leading-tight group-hover:text-primary">
 {{ period.period_title }}
 </p>
 <div class="flex items-center gap-2 mt-0.5">
 <p class="text-xs text-slate-400 font-normal">
 {{ period.created_at_label }}
 </p>
 <span
 v-if="period.deadline_date"
 class="text-[10.5px] px-1.5 py-0.5 rounded font-medium"
 :class="period.is_overdue ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-500'"
 >
 Deadline: {{ period.deadline_date }}
 </span>
 </div>
 </Link>
 </td>

 <!-- Status -->
 <td class="px-5 py-3.5 text-center">
 <!-- Status AKTIF -->
 <span
 v-if="period.status === 'proses' && !period.is_overdue"
 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#021344] text-white shadow-xs"
 >
 <span class="w-2 h-2 rounded-full bg-cyan-400 shrink-0"></span>
 AKTIF
 </span>

 <!-- Status NONAKTIF -->
 <span
 v-else
 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider bg-[#eff4fa] text-slate-500 border border-slate-200/80"
 >
 <span class="w-2 h-2 rounded-full bg-slate-400 shrink-0"></span>
 NONAKTIF
 </span>
 </td>

 <!-- Progress -->
 <td class="px-5 py-3.5 text-center">
 <div class="w-44 mx-auto text-center">
 <p class="text-xs font-medium text-slate-400 mb-1">
 <span class="text-slate-700 font-semibold">{{ period.my_checked_services }}/{{ period.my_total_services }}</span> layanan
 </p>
 <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
 <div
 class="h-full rounded-full transition-all duration-500"
 :class="period.my_progress_percent === 100 ? 'bg-emerald-500' : 'bg-cyan-500'"
 :style="{ width: `${period.my_progress_percent}%` }"
 ></div>
 </div>
 </div>
 </td>

 <!-- Aksi (Cek Layanan / Lihat Layanan) -->
 <td class="px-5 py-3.5 text-center">
 <div class="flex items-center justify-center">
 <Link
 :href="route('tester.periods.services', period.id)"
 :title="period.status === 'proses' && !period.is_overdue ? 'Lakukan pengecekan layanan' : 'Lihat hasil pengecekan'"
 class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all active:scale-95 shadow-xs"
 :class="period.status === 'proses' && !period.is_overdue
 ? 'bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white shadow-primary/10'
 : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200/80'"
 >
 <svg v-if="period.status === 'proses' && !period.is_overdue" class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
 </svg>
 <svg v-else class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
 <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
 </svg>
 <span>{{ period.status === 'proses' && !period.is_overdue ? 'Cek Layanan' : 'Lihat Layanan' }}</span>
 </Link>
 </div>
 </td>
 </tr>

 <!-- Empty State -->
 <tr v-if="periods.length === 0">
 <td colspan="4" class="py-12 text-center text-slate-400">
 <div class="flex flex-col items-center justify-center">
 <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3 text-slate-400">
 <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round"/>
 <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/>
 </svg>
 </div>
 <p class="font-semibold text-slate-600 text-sm">Belum ada periode penugasan aktif</p>
 <p class="text-xs text-slate-400 mt-0.5">Tidak ada periode aktif yang ditugaskan kepada Anda saat ini.</p>
 </div>
 </td>
 </tr>
 </tbody>
 </table>
 </div>
 </div>
 </AdminLayout>
</template>
