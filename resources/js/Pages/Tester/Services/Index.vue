<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import Pagination from '@/Components/Pagination.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const props = defineProps({
 periodInfo: { type: Object, default: null },
 services: { type: Object, default: () => ({ data: [], links: [], from: 0, to: 0, total: 0, current_page: 1 }) },
 stats: { type: Object, default: () => ({ total_assigned: 0, checked: 0, pending: 0, issues: 0, normal: 0, progress_percent: 0 }) },
 categories: { type: Array, default: () => [] },
 levels: { type: Array, default: () => ['Level 1', 'Level 2', 'Level 3'] },
 filters: { type: Object, default: () => ({ search: '', status: '', level: '', category: '' }) },
 flash: { type: Object, default: () => ({ success: null, error: null }) },
});

// ─── Filter & Search State ─────────────────────────────────────
const searchQuery = ref(props.filters?.search || '');
const filterStatus = ref(props.filters?.status || '');
const filterLevel = ref(props.filters?.level || '');
const filterCategory = ref(props.filters?.category || '');

let filterTimer = null;
watch([searchQuery, filterStatus, filterLevel, filterCategory], ([newSearch, newStatus, newLevel, newCat]) => {
 clearTimeout(filterTimer);
 filterTimer = setTimeout(() => {
 const routeName = props.periodInfo ? 'tester.periods.services' : 'tester.services.index';
 const routeParam = props.periodInfo ? props.periodInfo.id : undefined;

 router.get(route(routeName, routeParam), {
 search: newSearch,
 status: newStatus,
 level: newLevel,
 category: newCat,
 }, {
 preserveState: true,
 preserveScroll: true,
 replace: true,
 });
 }, 350);
});

function setQuickStatus(statusVal) {
 filterStatus.value = filterStatus.value === statusVal ? '' : statusVal;
}

function resetFilters() {
 searchQuery.value = '';
 filterStatus.value = '';
 filterLevel.value = '';
 filterCategory.value = '';
}

const hasActiveFilters = computed(() => {
 return !!(searchQuery.value || filterStatus.value || filterLevel.value || filterCategory.value);
});

// ─── Toast Notification ─────────────────────────────────────────
const toast = ref(props.flash?.success || '');
const toastType = ref('success');
let toastTimer = null;

function showToast(msg, type = 'success') {
 if (toastTimer) clearTimeout(toastTimer);
 toast.value = msg;
 toastType.value = type;
 toastTimer = setTimeout(() => { toast.value = ''; }, 4000);
}

// ─── Testing Modal State ─────────────────────────────────────────
const showCheckModal = ref(false);
const selectedService = ref(null);
const checkForm = useForm({ status: '', description: '' });

const isEditMode = computed(() => {
 return selectedService.value && selectedService.value.status !== 'pending';
});

function openCheckModal(service) {
 selectedService.value = service;
 // Pre-select existing status if already tested, otherwise default to normal
 checkForm.status = service.status === 'pending' ? 'normal' : service.status;
 checkForm.description = service.last_description || '';
 checkForm.clearErrors();
 showCheckModal.value = true;
}

function submitCheck() {
 checkForm.post(route('tester.submit_check', selectedService.value.id), {
 preserveScroll: true,
 onSuccess: (pageObj) => {
 if (pageObj.props.flash && pageObj.props.flash.error) {
 showToast(pageObj.props.flash.error, 'error');
 } else {
 showCheckModal.value = false;
 showToast(isEditMode.value ? 'Hasil pengujian berhasil diperbarui.' : 'Hasil pengecekan berhasil disimpan.');
 }
 },
 });
}
</script>

<template>
 <Head title="Layanan yang Ditugaskan - MAJADIGI CHECKER" />

 <AdminLayout
 title="Layanan yang Ditugaskan"
 :subtitle="periodInfo ? `Periode ${periodInfo.period_title} (${periodInfo.month_year})` : 'Daftar penugasan pengujian layanan digital'"
 >

 <!-- ── Toast Flash Notification ───────────────────────── -->
 <Transition
 enter-active-class="transition-all duration-300 ease-out"
 enter-from-class="opacity-0 -translate-y-3 translate-x-2"
 enter-to-class="opacity-100 translate-y-0 translate-x-0"
 leave-active-class="transition-all duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0 translate-x-0"
 leave-to-class="opacity-0 -translate-y-3 translate-x-2"
 >
 <div
 v-if="toast"
 :class="[
 'fixed top-5 right-5 z-[140] flex items-center justify-between gap-3 px-4.5 py-3 rounded-2xl shadow-xl text-sm font-semibold border backdrop-blur-md max-w-md',
 toastType === 'success'
 ? 'bg-emerald-50/95 border-emerald-200 text-emerald-800 shadow-emerald-900/10'
 : 'bg-rose-50/95 border-rose-200 text-rose-800 shadow-rose-900/10'
 ]"
 >
 <div class="flex items-center gap-2.5">
 <div
 class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 shadow-xs"
 :class="toastType === 'success' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white'"
 >
 <svg v-if="toastType === 'success'" class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 <svg v-else class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
 </svg>
 </div>
 <span>{{ toast }}</span>
 </div>
 <button
 type="button"
 @click="toast = ''"
 class="p-1 rounded-xl transition-colors hover:bg-black/5 text-slate-500 cursor-pointer"
 aria-label="Tutup notifikasi"
 >
 <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
 </svg>
 </button>
 </div>
 </Transition>

 <div class="space-y-6 max-w-7xl mx-auto">

 <!-- ── Top Action Bar (Back to Periods List) ──────────────── -->
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-white p-3.5 sm:p-4 rounded-2xl border border-slate-200/80 shadow-xs">
 <Link
 :href="route('tester.services.index')"
 class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-50 hover:bg-slate-100 border border-slate-200 text-slate-700 hover:text-[#1e3a8a] text-xs sm:text-sm font-bold transition-all active:scale-95 group w-fit"
 >
 <svg class="w-4 h-4 transition-transform group-hover:-translate-x-0.5 text-slate-500 group-hover:text-[#1e3a8a]" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
 </svg>
 <span>Kembali ke Daftar Periode</span>
 </Link>

 <div v-if="periodInfo" class="flex items-center gap-2">
 <span
 v-if="periodInfo.status === 'proses' && !periodInfo.is_overdue"
 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase bg-[#1e3a8a] text-white shadow-xs"
 >
 <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
 Status: Proses
 </span>
 <span
 v-else-if="periodInfo.status === 'proses' && periodInfo.is_overdue"
 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase bg-rose-100 border border-rose-200 text-rose-600 shadow-xs"
 >
 <svg class="w-3.5 h-3.5 text-rose-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
 </svg>
 Status: Terkunci (Overdue)
 </span>
 <span
 v-else
 class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-bold uppercase bg-slate-100 border border-slate-200 text-slate-600"
 >
 <svg class="w-3.5 h-3.5 text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 Status: Selesai (Terkunci)
 </span>
 </div>
 </div>
 
 <template v-if="periodInfo">
 <!-- ── Header Banner ────────────────────────────────────── -->
    <div v-if="periodInfo" class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-4">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_3.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
            Pengujian Layanan
          </div>
          <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Pengecekan: {{ periodInfo.month_name || 'Periode Pengujian' }}</h2>
          <p class="text-white/80 text-xs sm:text-sm mt-1">Lakukan evaluasi dan catat status pengujian layanan digital yang ditugaskan kepada Anda.</p>
        </div>
      </div>
    </div>

    <!-- ── Deadline Banner ─────────────────────────────────── -->
 <div
 v-if="periodInfo && periodInfo.deadline_date && periodInfo.status === 'proses'"
 class="px-5 py-4 rounded-2xl border border-slate-200 bg-white shadow-sm flex items-center justify-between gap-4"
 >
 <div class="flex items-center gap-3">
 <div
 class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 border border-slate-100 bg-slate-50 text-slate-500"
 >
 <svg class="w-5 h-5 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
 </svg>
 </div>
 <div>
 <h4 class="font-bold text-sm text-slate-700">
 <template v-if="periodInfo.is_overdue">Tenggat Waktu Telah Lewat!</template>
 <template v-else-if="periodInfo.days_remaining === 0">Jatuh Tempo Hari Ini!</template>
 <template v-else>Tenggat Waktu: {{ periodInfo.deadline_date }}</template>
 </h4>
 <p class="text-xs mt-0.5 font-medium text-slate-500">
 <template v-if="periodInfo.is_overdue">Periode ini sudah melewati batas waktu {{ Math.abs(periodInfo.days_remaining) }} hari yang lalu.</template>
 <template v-else-if="periodInfo.days_remaining === 0">Segera selesaikan pengecekan layanan sebelum hari ini berakhir.</template>
 <template v-else>Sisa waktu: {{ periodInfo.days_remaining }} hari lagi.</template>
 </p>
 </div>
 </div>
 </div>

 <!-- ── Top Mini Stats Bar ────────────────────────────── -->
 <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5 sm:gap-4">
 <div class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between">
 <div>
 <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TOTAL TUGAS</p>
 <p class="text-2xl font-black text-slate-800 mt-0.5 leading-none">{{ props.stats.total_assigned }}</p>
 </div>
 <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
 </svg>
 </div>
 </div>

 <div
 @click="setQuickStatus('pending')"
 class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between cursor-pointer hover:border-slate-300 transition-all"
 :class="filterStatus === 'pending' ? 'ring-2 ring-primary border-primary' : ''"
 >
 <div>
 <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">BELUM DICEK</p>
 <p class="text-2xl font-black text-slate-800 mt-0.5 leading-none">{{ props.stats.pending }}</p>
 </div>
 <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <circle cx="12" cy="12" r="9"/>
 <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
 </svg>
 </div>
 </div>

 <div
 @click="setQuickStatus('normal')"
 class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between cursor-pointer hover:border-slate-300 transition-all"
 :class="filterStatus === 'normal' ? 'ring-2 ring-primary border-primary' : ''"
 >
 <div>
 <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">NORMAL</p>
 <p class="text-2xl font-black text-slate-800 mt-0.5 leading-none">{{ props.stats.normal }}</p>
 </div>
 <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 </div>
 </div>

 <div
 @click="setQuickStatus('gangguan')"
 class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm flex items-center justify-between cursor-pointer hover:border-slate-300 transition-all"
 :class="filterStatus === 'gangguan' ? 'ring-2 ring-primary border-primary' : ''"
 >
 <div>
 <p class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">TERKENDALA</p>
 <p class="text-2xl font-black text-slate-800 mt-0.5 leading-none">{{ props.stats.issues }}</p>
 </div>
 <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 text-slate-500 flex items-center justify-center">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
 </svg>
 </div>
 </div>
 </div>

 <!-- ── Main Workspace Card ───────────────────────────── -->
 <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">

 <!-- Section Header & Quick Filter Tabs -->
 <div class="p-6 sm:p-7 pb-4 border-b border-slate-100 space-y-4">
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
 <div>
 <h3 class="text-lg font-extrabold text-slate-800">Daftar Penugasan Layanan</h3>
 <p class="text-xs text-slate-400 mt-0.5">Workspace pengujian layanan digital untuk periode aktif</p>
 </div>
 <span class="self-start sm:self-auto text-xs font-semibold text-slate-600 bg-slate-50 border border-slate-200/90 px-3.5 py-1.5 rounded-full">
 Total: <strong class="text-slate-800 font-bold">{{ props.services.total || 0 }}</strong> layanan
 </span>
 </div>

 <!-- Quick Filter Tabs -->
 <div class="flex items-center gap-2 overflow-x-auto pb-1 text-xs">
 <button
 type="button"
 @click="filterStatus = ''"
 class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 cursor-pointer border flex items-center gap-1.5"
 :class="filterStatus === '' ? 'bg-slate-800 text-white border-slate-800 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
 >
 <span>Semua</span>
 <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="filterStatus === '' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
 {{ props.stats.total_assigned }}
 </span>
 </button>
 <button
 type="button"
 @click="filterStatus = 'pending'"
 class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 cursor-pointer border flex items-center gap-1.5"
 :class="filterStatus === 'pending' ? 'bg-slate-800 text-white border-slate-800 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>
 <span>Belum Dicek</span>
 <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="filterStatus === 'pending' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
 {{ props.stats.pending }}
 </span>
 </button>
 <button
 type="button"
 @click="filterStatus = 'normal'"
 class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 cursor-pointer border flex items-center gap-1.5"
 :class="filterStatus === 'normal' ? 'bg-slate-800 text-white border-slate-800 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 <span>Normal</span>
 <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="filterStatus === 'normal' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
 {{ props.stats.normal }}
 </span>
 </button>
 <button
 type="button"
 @click="filterStatus = 'gangguan'"
 class="px-3.5 py-1.5 rounded-xl font-bold transition-all shrink-0 cursor-pointer border flex items-center gap-1.5"
 :class="filterStatus === 'gangguan' ? 'bg-slate-800 text-white border-slate-800 shadow-xs' : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 <span>Terkendala</span>
 <span class="px-1.5 py-0.2 rounded-md text-[11px]" :class="filterStatus === 'gangguan' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700'">
 {{ props.stats.issues }}
 </span>
 </button>
 </div>

 <!-- Search and Dropdown Filters Toolbar -->
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 pt-2">
 <!-- Search Box -->
 <div class="relative sm:col-span-2 lg:col-span-5">
 <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
 <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
 </svg>
 </div>
 <input
 v-model="searchQuery"
 type="text"
 placeholder="Cari nama layanan, URL, atau kategori..."
 class="block w-full pl-10 pr-10 py-2.5 bg-slate-50/70 border border-slate-200 rounded-xl text-xs placeholder:text-slate-400 focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 focus:outline-none transition-all"
 />
 <button
 v-if="searchQuery"
 @click="searchQuery = ''"
 class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
 title="Hapus pencarian"
 >
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
 </svg>
 </button>
 </div>

 <!-- Status Filter -->
 <div class="lg:col-span-2">
 <select
 v-model="filterStatus"
 class="w-full py-2.5 px-3 bg-slate-50/70 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none text-slate-700"
 >
 <option value="">Semua Status</option>
 <option value="pending">Belum Dicek</option>
 <option value="normal">Normal</option>
 <option value="gangguan">Terkendala</option>
 </select>
 </div>

 <!-- Level Filter -->
 <div class="lg:col-span-2">
 <select
 v-model="filterLevel"
 class="w-full py-2.5 px-3 bg-slate-50/70 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none text-slate-700"
 >
 <option value="">Semua Level</option>
 <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
 </select>
 </div>

 <!-- Category Filter -->
 <div class="lg:col-span-2">
 <select
 v-model="filterCategory"
 class="w-full py-2.5 px-3 bg-slate-50/70 border border-slate-200 rounded-xl text-xs focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none text-slate-700"
 >
 <option value="">Semua Kategori</option>
 <option v-for="cat in categories" :key="cat" :value="cat">{{ cat }}</option>
 </select>
 </div>

 <!-- Reset Filter Button -->
 <div class="lg:col-span-1 flex items-center">
 <button
 v-if="hasActiveFilters"
 type="button"
 @click="resetFilters"
 class="w-full py-2.5 px-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition-colors flex items-center justify-center gap-1 cursor-pointer border border-slate-200"
 title="Reset semua filter"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
 Reset
 </button>
 </div>
 </div>
 </div>

 <!-- Table Content -->
 <div class="overflow-x-auto">
 <table class="w-full text-left border-collapse">
 <thead>
 <tr class="bg-slate-50/90 border-b border-slate-200">
 <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-14 text-center">No</th>
 <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider min-w-[260px]">Nama Layanan</th>
 <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-36">Kategori & Level</th>
 <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider min-w-[200px]">Status & Hasil</th>
 <th class="py-3.5 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center w-28">Aksi</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100 text-xs sm:text-sm">
 <!-- Empty State: No Data -->
 <tr v-if="!services.data || services.data.length === 0">
 <td colspan="5" class="py-14 px-5 text-center">
 <div class="flex flex-col items-center justify-center max-w-md mx-auto text-slate-400">
 <div class="w-14 h-14 rounded-2xl bg-primary border border-primary flex items-center justify-center text-primary shadow-sm mb-3">
 <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
 </svg>
 </div>
 <p class="text-base font-bold text-slate-700">
 {{ hasActiveFilters ? 'Tidak ada layanan yang sesuai filter' : 'Belum Ada Layanan yang Ditugaskan' }}
 </p>
 <p class="text-xs text-slate-400 mt-1">
 {{ hasActiveFilters ? 'Coba ubah kata kunci pencarian atau sesuaikan opsi filter Anda.' : 'Admin belum membagikan daftar penugasan layanan untuk periode ini.' }}
 </p>
 <button
 v-if="hasActiveFilters"
 type="button"
 @click="resetFilters"
 class="mt-4 px-4 py-2 rounded-xl bg-primary text-white text-xs font-semibold shadow-md shadow-primary/20 transition-all cursor-pointer inline-flex items-center gap-1.5"
 >
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
 Reset Filter
 </button>
 </div>
 </td>
 </tr>

 <!-- Row List -->
 <tr
 v-for="(service, index) in services.data"
 :key="service.id"
 class="hover:bg-slate-50/60 transition-colors group"
 >
 <!-- No -->
 <td class="py-4 px-5 text-slate-400 font-medium text-center align-top pt-4.5">
 {{ (services.current_page - 1) * 10 + index + 1 }}
 </td>

 <!-- Nama Layanan & URL -->
 <td class="py-4 px-5 align-top">
 <div class="font-bold text-slate-800 text-sm group-hover:text-primary transition-colors">
 {{ service.name }}
 </div>
 <div class="flex items-center gap-1.5 mt-1">
 <a
 v-if="service.url"
 :href="service.url"
 target="_blank"
 class="text-xs text-slate-500 hover:text-primary hover:underline inline-flex items-center gap-1 truncate max-w-[320px] font-normal"
 title="Buka URL Layanan di tab baru"
 >
 <span class="truncate">{{ service.url }}</span>
 <svg class="w-3 h-3 shrink-0 text-slate-400 group-hover:text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
 </a>
 <span v-else class="text-xs text-slate-300">-</span>
 </div>
 </td>

 <!-- Kategori & Level -->
 <td class="py-4 px-5 align-top">
 <div class="flex flex-col items-start gap-1.5">
 <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200/70">
 {{ service.category || 'Umum' }}
 </span>
 <span
 v-if="service.level && service.level !== '-'"
 class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
 :class="service.level === 'Level 1' ? 'bg-primary text-white border border-primary' : (service.level === 'Level 2' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200/80' : (service.level === 'Level 3' ? 'bg-purple-50 text-purple-700 border border-purple-200/80' : 'bg-slate-100 text-slate-600 border border-slate-200/60'))"
 >
 {{ service.level }}
 </span>
 </div>
 </td>

 <!-- Status & Hasil Pengujian -->
 <td class="py-4 px-5 align-top">
 <!-- Status: Belum Dicek -->
 <div v-if="service.status === 'pending'" class="flex items-center gap-1.5">
 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200/90 shadow-2xs">
 <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 7v5l3 3"/></svg>
 Belum Dicek
 </span>
 </div>

 <!-- Status: Normal -->
 <div v-else-if="service.status === 'normal'" class="space-y-1">
 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/90 shadow-2xs">
 <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 Normal
 </span>
 <p v-if="service.checked_at" class="text-[11px] text-slate-400 font-medium">
 Diperiksa {{ service.checked_at }}
 </p>
 <p v-if="service.last_description" class="text-[11px] text-slate-600 italic line-clamp-1 max-w-[280px]">
 "{{ service.last_description }}"
 </p>
 </div>

 <!-- Status: Terkendala -->
 <div v-else class="space-y-1">
 <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200/90 shadow-2xs">
 <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 Terkendala
 </span>
 <p v-if="service.checked_at" class="text-[11px] text-slate-400 font-medium">
 Diperiksa {{ service.checked_at }}
 </p>
 <div v-if="service.last_description" class="inline-block bg-rose-50/70 border border-rose-100 rounded-lg px-2.5 py-1 text-[11px] text-rose-800 max-w-[280px] truncate" :title="service.last_description">
 <span class="font-semibold">Kendala:</span> {{ service.last_description }}
 </div>
 </div>
 </td>

 <!-- Action Button (Cek vs Ubah) -->
 <td class="py-4 px-5 text-center align-top pt-4">
 <!-- Primary Cek Button for Pending -->
 <button
 v-if="service.status === 'pending'"
 type="button"
 @click="!(periodInfo.status !== 'proses' || periodInfo.is_overdue) && openCheckModal(service)"
 :disabled="periodInfo.status !== 'proses' || periodInfo.is_overdue"
 class="inline-flex items-center justify-center gap-1.5 px-4 py-1.5 rounded-xl font-bold text-xs shadow-xs transition-all border"
 :class="(periodInfo.status !== 'proses' || periodInfo.is_overdue) ? 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-primary text-white hover:shadow active:scale-95 cursor-pointer border-primary'"
 :title="(periodInfo.status !== 'proses' || periodInfo.is_overdue) ? 'Periode sudah ditutup atau melewati batas waktu' : 'Lakukan Pengecekan Layanan'"
 >
 <svg v-if="periodInfo.status !== 'proses' || periodInfo.is_overdue" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
 <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 Cek
 </button>

 <!-- Subtle Ubah Button for Already Checked -->
 <button
 v-else
 type="button"
 @click="!(periodInfo.status !== 'proses' || periodInfo.is_overdue) && openCheckModal(service)"
 :disabled="periodInfo.status !== 'proses' || periodInfo.is_overdue"
 class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 rounded-xl font-semibold text-xs shadow-2xs transition-all border"
 :class="(periodInfo.status !== 'proses' || periodInfo.is_overdue) ? 'bg-slate-50 text-slate-400 border-slate-200 cursor-not-allowed' : 'bg-slate-50 hover:bg-slate-100 text-slate-700 border-slate-200 hover:border-slate-300 active:scale-95 cursor-pointer'"
 :title="(periodInfo.status !== 'proses' || periodInfo.is_overdue) ? 'Periode sudah ditutup atau melewati batas waktu' : 'Ubah Hasil Pengecekan'"
 >
 <svg v-if="periodInfo.status !== 'proses' || periodInfo.is_overdue" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
 <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
 Ubah
 </button>
 </td>
 </tr>
 </tbody>
 </table>
 </div>

 <!-- Pagination Component -->
 <div v-if="services.links && services.links.length > 3" class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
 <Pagination :meta="services" />
 </div>

 </div>

 </template>
 
 <template v-else>
 <!-- EMPTY STATE TUGAS -->
 <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-8 flex flex-col items-center justify-center text-center min-h-[500px]">
 <div class="w-24 h-24 bg-slate-50 rounded-[2rem] flex items-center justify-center mb-6 border border-slate-100 shadow-inner">
 <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
 </div>
 <h3 class="text-2xl font-black text-slate-800 mb-3 tracking-tight">Belum Ada Penugasan Aktif</h3>
 <p class="text-sm text-slate-500 max-w-md leading-relaxed mb-6">
 Saat ini Anda tidak memiliki penugasan pada periode yang sedang berjalan. Seluruh tugas pada periode sebelumnya telah selesai atau dikunci oleh sistem.
 </p>
 <div class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-semibold text-xs border border-slate-200">
 <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 Silakan tunggu informasi selanjutnya dari Admin Majadigi.
 </div>
 </div>
 </template>
 </div>

 <!-- ── Modal Uji Layanan / Edit Hasil Pengujian (Clean & Modern) ── -->
 <Transition
 enter-active-class="transition duration-200 ease-out"
 enter-from-class="opacity-0 scale-95"
 enter-to-class="opacity-100 scale-100"
 leave-active-class="transition duration-150 ease-in"
 leave-from-class="opacity-100 scale-100"
 leave-to-class="opacity-0 scale-95"
 >
 <div
 v-if="showCheckModal && selectedService"
 class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
 @click.self="showCheckModal = false"
 >
 <div class="bg-white rounded-3xl shadow-2xl shadow-slate-950/30 w-full max-w-xl overflow-hidden flex flex-col max-h-[85vh] border border-slate-200/90 my-auto">
 
 <!-- Modal Header -->
 <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white">
 <div class="flex items-center gap-3 min-w-0">
 <div
 class="w-8 h-8 rounded-xl flex items-center justify-center font-bold shrink-0 shadow-xs border"
 :class="isEditMode ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-primary border-primary text-white'"
 >
 <svg v-if="!isEditMode" class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
 </svg>
 <svg v-else class="w-4 h-4 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
 </svg>
 </div>
 <div class="min-w-0">
 <h3 class="text-sm sm:text-base font-extrabold text-slate-800 leading-snug">
 {{ isEditMode ? 'Edit Hasil Pengujian' : 'Uji Layanan' }}
 </h3>
 <p class="text-xs text-slate-500 font-medium truncate">
 {{ selectedService.name }}
 </p>
 </div>
 </div>
 <button
 type="button"
 @click="showCheckModal = false"
 class="text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-xl p-1.5 transition-colors cursor-pointer"
 aria-label="Tutup modal"
 >
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
 </button>
 </div>

 <!-- Modal Body (Internal Scroll) -->
 <div class="p-5 sm:p-6 overflow-y-auto space-y-4 text-xs">
 
 <!-- SECTION 1: Informasi Layanan (Compact Metadata Layout) -->
 <div class="bg-slate-50/80 rounded-2xl border border-slate-200/80 p-4 space-y-2.5">
 <div class="flex items-center justify-between gap-2 flex-wrap">
 <div class="flex items-center gap-1.5 flex-wrap">
 <span class="px-2.5 py-0.5 rounded-lg bg-white border border-slate-200 text-slate-700 font-semibold text-xs shadow-2xs">
 {{ selectedService.category || 'Umum' }}
 </span>
 <span
 v-if="selectedService.level && selectedService.level !== '-'"
 class="px-2.5 py-0.5 rounded-full bg-primary border border-primary text-white font-bold text-[11px]"
 >
 {{ selectedService.level }}
 </span>
 </div>
 <div v-if="selectedService.url">
 <a
 :href="selectedService.url"
 target="_blank"
 class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-primary text-white font-bold text-xs shadow-xs transition-all"
 >
 Buka URL Layanan
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
 </a>
 </div>
 </div>

 <div v-if="selectedService.url" class="flex items-center gap-1.5 text-slate-500 pt-0.5">
 <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
 <a :href="selectedService.url" target="_blank" class="text-primary hover:underline font-mono truncate text-[11px]">
 {{ selectedService.url }}
 </a>
 </div>

 <p v-if="selectedService.deskripsi_layanan" class="text-slate-600 bg-white border border-slate-200/70 rounded-xl p-2.5 text-xs leading-relaxed">
 {{ selectedService.deskripsi_layanan }}
 </p>

 <div v-if="selectedService.tester_instruction" class="bg-amber-50 border border-amber-200 rounded-xl p-3 text-amber-900 shadow-2xs">
 <p class="font-extrabold text-[11px] text-amber-800 uppercase tracking-wider mb-1 flex items-center gap-1.5">
 <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 Instruksi Khusus
 </p>
 <p class="text-xs leading-relaxed whitespace-pre-wrap">{{ selectedService.tester_instruction }}</p>
 </div>

 <!-- Previous Result Banner if Editing -->
 <div v-if="isEditMode && selectedService.checked_at" class="bg-white border border-slate-200 rounded-xl p-2.5 flex items-center justify-between gap-2 text-slate-500">
 <span>Pengecekan sebelumnya: <strong class="text-slate-700 font-semibold">{{ selectedService.checked_at }}</strong></span>
 <span
 class="px-2 py-0.5 rounded-md font-bold text-[10px] uppercase"
 :class="selectedService.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
 >
 {{ selectedService.status === 'normal' ? 'NORMAL' : 'TERKENDALA' }}
 </span>
 </div>
 </div>

 <!-- SECTION 2: Hasil Pengecekan (Horizontal Segmented Selection) -->
 <form @submit.prevent="submitCheck" class="space-y-4">
 <div>
 <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
 Hasil Pengecekan <span class="text-rose-500">*</span>
 </label>
 
 <div class="grid grid-cols-2 gap-3">
 <!-- NORMAL OPTION -->
 <label class="relative cursor-pointer select-none">
 <input type="radio" v-model="checkForm.status" value="normal" class="peer sr-only">
 <div
 class="p-3.5 rounded-2xl border transition-all flex items-center gap-3 bg-white"
 :class="checkForm.status === 'normal'
 ? 'border-emerald-500 bg-emerald-50/40 text-emerald-950 ring-2 ring-emerald-500/20 shadow-xs'
 : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
 >
 <div
 class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 font-bold transition-colors"
 :class="checkForm.status === 'normal' ? 'bg-emerald-500 text-white shadow-xs' : 'bg-slate-100 text-slate-400 border border-slate-200'"
 >
 <svg class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 </div>
 <div class="min-w-0">
 <p class="font-extrabold text-xs leading-none">NORMAL</p>
 <p class="text-[10px] mt-1 text-slate-500 font-medium truncate">Berfungsi normal</p>
 </div>
 </div>
 </label>

 <!-- KENDALA OPTION -->
 <label class="relative cursor-pointer select-none">
 <input type="radio" v-model="checkForm.status" value="gangguan" class="peer sr-only">
 <div
 class="p-3.5 rounded-2xl border transition-all flex items-center gap-3 bg-white"
 :class="checkForm.status === 'gangguan'
 ? 'border-rose-500 bg-rose-50/40 text-rose-950 ring-2 ring-rose-500/20 shadow-xs'
 : 'border-slate-200 text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
 >
 <div
 class="w-7 h-7 rounded-full flex items-center justify-center shrink-0 font-bold transition-colors"
 :class="checkForm.status === 'gangguan' ? 'bg-rose-500 text-white shadow-xs' : 'bg-slate-100 text-slate-400 border border-slate-200'"
 >
 <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
 </div>
 <div class="min-w-0">
 <p class="font-extrabold text-xs leading-none">KENDALA</p>
 <p class="text-[10px] mt-1 text-slate-500 font-medium truncate">Ada error / gangguan</p>
 </div>
 </div>
 </label>
 </div>
 <p v-if="checkForm.errors.status" class="mt-1 text-xs font-semibold text-rose-500">{{ checkForm.errors.status }}</p>
 </div>

 <!-- SECTION 3: Catatan Pengujian / Keterangan Kendala -->
 <div>
 <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
 {{ checkForm.status === 'gangguan' ? 'Catatan / Keterangan Kendala' : 'Catatan Pengujian' }}
 <span v-if="checkForm.status === 'gangguan'" class="text-rose-600 font-bold lowercase">(wajib)</span>
 <span v-else class="text-slate-400 font-normal lowercase">(opsional)</span>
 </label>
 <textarea
 v-model="checkForm.description"
 rows="3"
 maxlength="5000"
 class="w-full rounded-xl bg-slate-50/70 border border-slate-200 text-xs focus:bg-white focus:ring-2 focus:ring-primary/20 focus:border-primary p-3 leading-relaxed placeholder:text-slate-400 outline-none transition-all"
 :placeholder="checkForm.status === 'gangguan' ? 'Jelaskan secara spesifik kendala yang dialami (misal: halaman 404, lambat diakses, SSL expired, gagal login, dsb.)...' : 'Tambahkan catatan pengujian bila ada (opsional)...'"
 ></textarea>
 <p v-if="checkForm.errors.description" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.description }}</p>
 </div>
 </form>
 </div>

 <!-- Modal Footer -->
 <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/60 flex items-center justify-end gap-2.5 rounded-b-3xl">
 <button
 type="button"
 @click="showCheckModal = false"
 class="px-4.5 py-2 rounded-xl font-semibold text-xs text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-800 transition-colors cursor-pointer"
 >
 Batal
 </button>
 <button
 type="button"
 @click="submitCheck"
 :disabled="checkForm.processing"
 class="px-5 py-2 rounded-xl font-bold text-xs text-white bg-primary hover:bg-primary shadow-md shadow-primary/20 transition-all disabled:opacity-70 flex items-center gap-1.5 cursor-pointer active:scale-95"
 >
 <svg v-if="checkForm.processing" class="animate-spin w-3.5 h-3.5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
 <span>{{ isEditMode ? 'Simpan Perubahan' : 'Simpan Hasil Pengecekan' }}</span>
 </button>
 </div>

 </div>
 </div>
 </Transition>

 </AdminLayout>
</template>
