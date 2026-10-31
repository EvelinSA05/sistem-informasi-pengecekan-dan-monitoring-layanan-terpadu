<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ClientPagination from '@/Components/ClientPagination.vue';

const props = defineProps({
 period: { type: Object, required: true },
 distribution: { type: Array, default: () => [] },
 troubled_services: { type: Array, default: () => [] },
});

// Layanan Terkendala state & actions
const troubledList = ref([...props.troubled_services]);
watch(() => props.troubled_services, (val) => {
 troubledList.value = [...(val || [])];
}, { deep: true });

const isUpdatingSolved = ref({});
async function toggleTroubledSolved(item) {
 if (!item.issue_id) return;
 isUpdatingSolved.value[item.id] = true;
 try {
  const response = await axios.post(route('admin.services.issues.toggle_solved', item.issue_id));
  if (response.data?.success) {
   item.has_solved = response.data.has_solved;
   item.has_solved_label = item.has_solved ? 'sudah tindak lanjuti' : 'belum tindak lanjuti';
   showToast(response.data.message, 'success');
  }
 } catch (e) {
  showToast('Gagal mengubah status tindak lanjut kendala.', 'error');
 } finally {
  isUpdatingSolved.value[item.id] = false;
 }
}

const page = usePage();
const statusFilter = ref('all');
const searchQuery = ref('');
const isTableVisible = ref(true);
const currentPage = ref(1);
const perPage = ref(10);

// Toast
const toast = ref('');
const toastType = ref('success');
let toastTimer = null;
function showToast(msg, type = 'success') {
 if (toastTimer) clearTimeout(toastTimer);
 toast.value = msg;
 toastType.value = type;
 toastTimer = setTimeout(() => { toast.value = ''; }, 4000);
}
watch(() => page.props.flash, (f) => {
 if (f?.success) showToast(f.success, 'success');
 else if (f?.error) showToast(f.error, 'error');
}, { deep: true });
onMounted(() => {
 if (page.props.flash?.success) showToast(page.props.flash.success, 'success');
 else if (page.props.flash?.error) showToast(page.props.flash.error, 'error');
});

// Status Toggle
const isUpdatingStatus = ref(false);
const showOverdueModal = ref(false);

function togglePeriodStatus() {
 const nextStatus = props.period.status === 'proses' ? 'selesai' : 'proses';

 if (nextStatus === 'proses' && props.period.is_overdue) {
  showOverdueModal.value = true;
  return;
 }

 isUpdatingStatus.value = true;
 router.put(route('admin.periods.update', props.period.id), { status: nextStatus }, {
 preserveScroll: true,
 onError: (errors) => {
 showToast(errors.status || Object.values(errors)[0] || 'Gagal mengubah status.', 'error');
 },
 onFinish: () => { isUpdatingStatus.value = false; }
 });
}

// Filtered Services
const allServices = computed(() => {
 const list = [];
 props.distribution.forEach(g => {
 g.services.forEach(s => {
 list.push({ ...s, tester_name: g.name, tester_bidang: g.bidang, tester_initials: g.initials });
 });
 });
 return list;
});
watch([searchQuery, statusFilter], () => { currentPage.value = 1; });

const filteredServices = computed(() => {
 return allServices.value.filter(s => {
 const matchStatus = statusFilter.value === 'all' || s.status === statusFilter.value;
 const q = searchQuery.value.toLowerCase().trim();
 const matchSearch = !q || s.service_name.toLowerCase().includes(q) || s.category.toLowerCase().includes(q) || s.tester_name.toLowerCase().includes(q);
 return matchStatus && matchSearch;
 });
});

const paginatedServices = computed(() => {
 const start = (currentPage.value - 1) * perPage.value;
 return filteredServices.value.slice(start, start + perPage.value);
});

const paginationMeta = computed(() => {
 const total = filteredServices.value.length;
 return {
 total: total,
 per_page: perPage.value,
 current_page: currentPage.value,
 last_page: Math.ceil(total / perPage.value) || 1,
 };
});

// Helpers
function getTesterProgress(g) {
 if (!g.total_services) return 0;
 return Math.round((g.checked_services / g.total_services) * 100);
}
function getTesterStatusLabel(g) {
 const p = getTesterProgress(g);
 if (p === 100) return 'Selesai';
 if (p > 0) return 'Proses';
 return 'Menunggu';
}

const avatarPalette = {
 'SD': 'bg-slate-600', 'AF': 'bg-primary', 'AP': 'bg-primary',
 'DR': 'bg-slate-600', 'BS': 'bg-slate-700', 'TK': 'bg-slate-500',
};
function getAvatarColor(initials, name) {
 if (avatarPalette[initials]) return avatarPalette[initials];
 const c = ['bg-slate-600','bg-primary','bg-slate-700','bg-slate-500','bg-primary'];
 return c[(name?.charCodeAt(0) ?? 0) % c.length];
}

const serviceIcons = {
 globe: `<svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/></svg>`,
 megaphone: `<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/></svg>`,
 box: `<svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>`,
 chart: `<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>`,
 currency: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
 document: `<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
 certificate: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>`,
 academic: `<svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>`,
 archive: `<svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>`,
 users: `<svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>`,
 database: `<svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path stroke-linecap="round" d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path stroke-linecap="round" d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>`,
 'shopping-cart': `<svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>`,
};
function getServiceIcon(k) { return serviceIcons[k] || serviceIcons['globe']; }
</script>

<template>
 <Head :title="`Detail ${period.title} - MAJADIGI CHECKER`" />

 <AdminLayout title="Periode Pengecekan" subtitle="Atur periode dan distribusi layanan">

 <!-- Toast -->
 <Transition
 enter-active-class="transition duration-300 ease-out"
 enter-from-class="opacity-0 translate-y-2"
 enter-to-class="opacity-100 translate-y-0"
 leave-active-class="transition duration-200 ease-in"
 leave-from-class="opacity-100 translate-y-0"
 leave-to-class="opacity-0 translate-y-2"
 >
 <div v-if="toast" :class="['fixed top-5 right-5 z-[120] flex items-center gap-3 px-4 py-3 rounded-2xl shadow-lg text-sm font-semibold border max-w-md', toastType === 'success' ? 'bg-emerald-50 border-emerald-200 text-emerald-800' : 'bg-rose-50 border-rose-200 text-rose-800']">
 <div :class="['w-6 h-6 rounded-lg flex items-center justify-center shrink-0', toastType === 'success' ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white']">
 <svg v-if="toastType === 'success'" class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 <svg v-else class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
 </div>
 <span class="flex-1">{{ toast }}</span>
 <button type="button" @click="toast = ''" class="p-1 rounded-lg hover:bg-black/5 text-current/50 cursor-pointer"><svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
 </div>
 </Transition>

 <div class="space-y-5 max-w-7xl mx-auto">

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 1. HEADER CARD -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="relative rounded-2xl overflow-hidden bg-primary p-6 shadow-lg">
  <!-- Dynamic Background Image -->
  <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>
  <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
 <!-- Dot texture overlay -->
 <div class="absolute inset-0 opacity-[0.07]" style="background-image: radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px); background-size: 20px 20px;"></div>
 <!-- Diagonal line texture -->
 <div class="absolute inset-0 opacity-[0.04]" style="background-image: repeating-linear-gradient(45deg, transparent, transparent 35px, rgba(255,255,255,1) 35px, rgba(255,255,255,1) 36px);"></div>
 <!-- Subtle glow circle -->
 <div class="absolute -top-20 -right-20 w-60 h-60 bg-cyan-400/10 rounded-full blur-3xl"></div>
 <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-primary/10 rounded-full blur-2xl"></div>

 <div class="relative z-10">
 <!-- Kembali -->
 <Link :href="route('admin.periods.index')" class="inline-flex items-center gap-1.5 text-xs text-white/80 hover:text-white transition-colors group mb-4 cursor-pointer">
 <span class="transition-transform group-hover:-translate-x-0.5">&larr;</span>
 <span>Kembali</span>
 </Link>

 <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-4">
 <div>
 <div class="flex items-center gap-3 flex-wrap">
 <h2 class="text-xl font-bold text-white">Detail Periode: {{ period.title }}</h2>
 <span v-if="period.status === 'proses'" class="text-[11px] font-bold uppercase tracking-wide text-cyan-100 bg-white/15 border border-white/20 px-2.5 py-0.5 rounded-full backdrop-blur-sm">AKTIF</span>
 <span v-else class="text-[11px] font-bold uppercase tracking-wide text-white/80 bg-white/10 border border-white/15 px-2.5 py-0.5 rounded-full backdrop-blur-sm">NONAKTIF</span>
 </div>
 <p class="text-xs text-white/70 mt-1.5">
 Dibuat pada {{ period.created_at_label }} oleh {{ period.creator_name }}
 <template v-if="period.deadline_date"> &bull; Tenggat Waktu: {{ period.deadline_date }}</template>
 </p>
 </div>

 <div class="flex items-center gap-2 flex-wrap shrink-0">
 <Link :href="route('admin.periods.edit', period.id)" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-white/15 border border-white/20 rounded-lg hover:bg-white/25 transition-colors cursor-pointer backdrop-blur-sm">
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/></svg>
 Edit Periode
 </Link>
 <a :href="route('admin.periods.export.excel', period.id)" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-white/15 border border-white/20 rounded-lg hover:bg-white/25 transition-colors cursor-pointer backdrop-blur-sm">
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
 Export Excel
 </a>
 <a :href="route('admin.periods.export.pdf', period.id)" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-white/15 border border-white/20 rounded-lg hover:bg-white/25 transition-colors cursor-pointer backdrop-blur-sm">
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
 Export PDF
 </a>
 <button type="button" @click="togglePeriodStatus" :disabled="isUpdatingStatus" class="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-semibold text-primary bg-white hover:bg-primary rounded-lg transition-colors cursor-pointer disabled:opacity-50 shadow-sm">
 <svg v-if="period.status === 'proses'" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
 <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
 {{ isUpdatingStatus ? 'Memproses...' : (period.status === 'proses' ? 'Selesaikan Periode' : 'Buka Kembali Periode') }}
 </button>
 </div>
 </div>
 </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 2. KPI CARDS (4 kolom, clean monokromatik) -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
 <!-- Total Layanan -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">TOTAL LAYANAN</span>
 <div class="w-8 h-8 rounded-xl bg-slate-50 text-slate-400 flex items-center justify-center">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900">{{ period.total_services }}</p>
 <p class="text-xs text-slate-400 mt-1">Layanan Digital Jatim</p>
 </div>

 <!-- Layanan Normal -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">LAYANAN NORMAL</span>
 <div class="w-8 h-8 rounded-xl bg-cyan-50 text-cyan-500 flex items-center justify-center">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900">{{ period.normal_services }}</p>
 <p class="text-xs text-cyan-600 mt-1 flex items-center gap-1.5">
 <span class="w-1.5 h-1.5 rounded-full bg-cyan-500"></span>
 Berjalan Optimal
 </p>
 </div>

 <!-- Terkendala -->
 <div
  @click="statusFilter = 'gangguan'; isTableVisible = true"
  class="bg-white rounded-2xl border border-slate-200 p-5 cursor-pointer hover:border-rose-300 hover:bg-rose-50/20 transition-all group"
  title="Klik untuk melihat layanan terkendala"
 >
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 group-hover:text-rose-500 uppercase tracking-wider transition-colors">TERKENDALA</span>
 <div class="w-8 h-8 rounded-xl bg-slate-50 group-hover:bg-rose-50 text-slate-400 group-hover:text-rose-500 flex items-center justify-center transition-colors">
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 </div>
 </div>
 <p class="text-3xl font-bold text-slate-900 group-hover:text-rose-600 transition-colors">{{ period.issues_services }}</p>
 <p class="text-xs text-slate-400 group-hover:text-rose-500 mt-1 transition-colors">Perlu Penanganan</p>
 </div>

 <!-- Progress Cek -->
 <div class="bg-white rounded-2xl border border-slate-200 p-5">
 <div class="flex items-center justify-between mb-3">
 <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">PROGRESS CEK</span>
 <span class="text-xs font-semibold text-slate-500">{{ period.checked_services }}/{{ period.total_services }}</span>
 </div>
 <div class="flex items-baseline justify-between">
 <p class="text-3xl font-bold text-slate-900">{{ period.progress_percent }}%</p>
 <span class="text-[11px] text-slate-400">dari total layanan</span>
 </div>
 <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
 <div class="h-full bg-primary rounded-full transition-all duration-500" :style="{ width: `${period.progress_percent}%` }"></div>
 </div>
 </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 2.5 SECTION: LAYANAN TERKENDALA (Tugas 13)          -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 p-6 overflow-hidden shadow-xs">
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-4 border-b border-slate-100">
   <div class="flex items-center gap-3">
    <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center border border-rose-200/60 shadow-xs">
     <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
     </svg>
    </div>
    <div>
     <div class="flex items-center gap-2">
      <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">Layanan Terkendala</h3>
      <span
       class="px-2.5 py-0.5 rounded-full text-[11px] font-bold"
       :class="troubledList.length > 0 ? 'bg-rose-100 text-rose-800 border border-rose-200' : 'bg-emerald-100 text-emerald-800 border border-emerald-200'"
      >
       {{ troubledList.length }} Layanan
      </span>
     </div>
     <p class="text-xs text-slate-400 mt-0.5">Daftar layanan yang dilaporkan mengalami kendala/gangguan pada periode ini</p>
    </div>
   </div>

   <div v-if="troubledList.length > 0" class="flex items-center gap-2 text-xs text-slate-500 font-medium">
    Ditindaklanjuti: 
    <strong class="text-emerald-600 font-bold">{{ troubledList.filter(t => t.has_solved).length }}</strong> / {{ troubledList.length }}
   </div>
  </div>

  <!-- Empty State jika tidak ada kendala -->
  <div v-if="troubledList.length === 0" class="p-8 rounded-xl bg-slate-50 border border-slate-200/80 text-center">
   <div class="w-12 h-12 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-2 border border-emerald-100">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
   </div>
   <p class="text-sm font-bold text-slate-700">Tidak Ada Layanan Terkendala</p>
   <p class="text-xs text-slate-400 mt-1">Seluruh layanan yang diperiksa pada periode ini berfungsi normal atau belum ditemukan kendala.</p>
  </div>

  <!-- List Layanan Terkendala -->
  <div v-else class="space-y-3">
   <div
    v-for="(item, idx) in troubledList"
    :key="item.id"
    class="rounded-xl border transition-all p-4"
    :class="item.has_solved ? 'bg-slate-50/70 border-slate-200' : 'bg-rose-50/40 border-rose-200 shadow-2xs'"
   >
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3">
     <div class="flex items-start gap-3 min-w-0">
      <span class="w-6 h-6 rounded-lg flex items-center justify-center text-xs font-bold shrink-0 mt-0.5"
       :class="item.has_solved ? 'bg-slate-200 text-slate-700' : 'bg-rose-100 text-rose-800'">
       {{ idx + 1 }}
      </span>
      <div class="min-w-0">
       <div class="flex items-center gap-2 flex-wrap">
        <h4 class="text-sm font-bold text-slate-800">{{ item.service_name }}</h4>
        <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">{{ item.category }}</span>
        <span v-if="item.level && item.level !== '-'" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-600 border border-slate-200">Level {{ item.level }}</span>
        <span
         class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
         :class="item.has_solved ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-amber-100 text-amber-800 border border-amber-200'"
        >
         <span class="w-1.5 h-1.5 rounded-full" :class="item.has_solved ? 'bg-emerald-500' : 'bg-amber-500'"></span>
         {{ item.has_solved_label }}
        </span>
       </div>
       <a v-if="item.service_url" :href="item.service_url" target="_blank" class="inline-flex items-center gap-1 text-[11px] text-primary hover:underline mt-1 truncate max-w-md">
        {{ item.service_url }}
        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
       </a>

       <!-- Catatan Kendala dari Tester -->
       <div class="mt-2.5 p-2.5 rounded-lg text-xs" :class="item.has_solved ? 'bg-white border border-slate-200 text-slate-600' : 'bg-white/80 border border-rose-200 text-slate-700'">
        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">
         <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
         Catatan Kendala Tester ({{ item.tester_name }}):
        </div>
        <p class="font-medium whitespace-pre-wrap leading-relaxed">{{ item.description }}</p>
       </div>
      </div>
     </div>

     <!-- Info Tester & Tombol Tindak Lanjut -->
     <div class="flex lg:flex-col items-center lg:items-end justify-between gap-2.5 shrink-0 pt-2 lg:pt-0 border-t lg:border-t-0 border-slate-100">
      <div class="text-left lg:text-right text-[11px] text-slate-400">
       <p>Diperiksa: <span class="font-semibold text-slate-700">{{ item.checked_at }}</span></p>
       <p>Oleh: <span class="font-semibold text-slate-700">{{ item.tester_name }}</span> ({{ item.tester_bidang }})</p>
      </div>

      <button
       type="button"
       @click="toggleTroubledSolved(item)"
       :disabled="isUpdatingSolved[item.id]"
       class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all border cursor-pointer active:scale-95 flex items-center gap-1.5 disabled:opacity-50"
       :class="item.has_solved
        ? 'bg-white hover:bg-slate-100 text-slate-600 border-slate-200'
        : 'bg-emerald-600 hover:bg-emerald-700 text-white border-emerald-600 shadow-xs'"
       :title="item.has_solved ? 'Ubah menjadi belum ditindaklanjuti' : 'Tandai kendala ini sudah ditindaklanjuti'"
      >
       <svg v-if="item.has_solved" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
       <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
       {{ isUpdatingSolved[item.id] ? 'Menyimpan...' : (item.has_solved ? 'Ubah Status' : 'Tandai Selesai') }}
      </button>
     </div>
    </div>
   </div>
  </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 3. DISTRIBUSI TESTER (clean card grid) -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 p-6">
 <div class="flex items-center justify-between mb-5">
 <div>
 <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider">DISTRIBUSI & BEBAN KERJA TESTER</h3>
 <p class="text-xs text-slate-400 mt-0.5">Penugasan pengecekan performa dan status layanan</p>
 </div>
 <span class="text-xs text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full">{{ distribution.length }} Personel</span>
 </div>

 <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
 <div
 v-for="group in distribution"
 :key="group.tester_id"
 class="rounded-xl border border-slate-200 p-4"
 >
 <!-- Avatar + Name + Fraction -->
 <div class="flex items-center justify-between gap-3 mb-3">
 <div class="flex items-center gap-3 min-w-0">
 <div
 :class="[
 'w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0',
 getTesterProgress(group) > 0 ? 'bg-primary' : 'bg-slate-300'
 ]"
 >{{ group.initials }}</div>
 <div class="min-w-0">
 <p class="text-sm font-semibold text-slate-800 truncate">{{ group.name }}</p>
 <p class="text-xs text-slate-400 truncate">{{ group.email }}</p>
 </div>
 </div>
 <span class="text-sm font-bold text-slate-700 shrink-0">{{ group.checked_services }}/{{ group.total_services }}</span>
 </div>

 <!-- Progress bar -->
 <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden mb-2">
 <div
 :class="['h-full rounded-full transition-all duration-300', getTesterProgress(group) === 100 ? 'bg-emerald-500' : 'bg-primary']"
 :style="{ width: `${getTesterProgress(group)}%` }"
 ></div>
 </div>

 <!-- Status + Pct -->
 <div class="flex items-center justify-between">
 <span :class="['text-xs', getTesterProgress(group) === 100 ? 'text-emerald-600 font-semibold' : (getTesterProgress(group) > 0 ? 'text-primary' : 'text-slate-400')]">
 {{ getTesterStatusLabel(group) }}
 </span>
 <span :class="['text-xs font-semibold', getTesterProgress(group) === 100 ? 'text-emerald-600' : (getTesterProgress(group) > 0 ? 'text-primary' : 'text-slate-400')]">
 {{ getTesterProgress(group) }}%
 </span>
 </div>
 </div>
 </div>
 </div>

 <!-- ═══════════════════════════════════════════════════ -->
 <!-- 4. TABEL DAFTAR LAYANAN -->
 <!-- ═══════════════════════════════════════════════════ -->
 <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
 <!-- Toolbar -->
 <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
 <button type="button" @click="isTableVisible = !isTableVisible" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-800 hover:text-primary transition-colors cursor-pointer">
 Daftar Layanan
 <span class="text-xs font-normal text-slate-400">({{ filteredServices.length }} dari {{ allServices.length }})</span>
 <svg class="w-4 h-4 text-slate-400 transition-transform duration-200" :class="isTableVisible ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
 </button>

 <div v-if="isTableVisible" class="flex items-center gap-2 flex-wrap">
 <div class="inline-flex p-0.5 bg-slate-100 rounded-lg text-xs text-slate-600">
 <button type="button" @click="statusFilter = 'all'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'all' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Semua</button>
 <button type="button" @click="statusFilter = 'normal'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'normal' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Normal</button>
 <button type="button" @click="statusFilter = 'gangguan'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'gangguan' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Kendala</button>
 <button type="button" @click="statusFilter = 'pending'" :class="['px-2.5 py-1 rounded-md transition-all cursor-pointer', statusFilter === 'pending' ? 'bg-white text-slate-800 shadow-sm font-semibold' : 'hover:text-slate-800']">Belum Dicek</button>
 </div>
 <div class="relative">
 <input v-model="searchQuery" type="text" placeholder="Cari layanan / tester..." class="pl-7 pr-3 py-1.5 text-xs bg-white border border-slate-200 rounded-lg text-slate-700 placeholder-slate-400 focus:outline-none focus:border-primary w-44 sm:w-52 transition-colors"/>
 <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
 </div>
 </div>
 </div>

 <!-- Table -->
 <div v-show="isTableVisible" class="overflow-x-auto">
 <table class="w-full text-left">
 <thead>
 <tr class="border-b border-slate-100 bg-slate-50/60 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
 <th class="py-3 px-6">Layanan</th>
 <th class="py-3 px-6">Kategori</th>
 <th class="py-3 px-6">Penanggung Jawab</th>
 <th class="py-3 px-6">Status</th>
 <th class="py-3 px-6">Waktu Cek</th>
 <th class="py-3 px-6">Keterangan</th>
 </tr>
 </thead>
 <tbody class="divide-y divide-slate-100 text-xs">
 <tr v-for="srv in paginatedServices" :key="srv.id" class="hover:bg-slate-50/50 transition-colors">
 <td class="py-3 px-6">
 <div class="flex items-center gap-3">
 <div class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center shrink-0 border border-slate-100" v-html="getServiceIcon(srv.icon)"></div>
 <div>
 <p class="font-semibold text-slate-800">{{ srv.service_name }}</p>
 <a v-if="srv.service_url" :href="srv.service_url" target="_blank" class="text-[11px] text-primary hover:underline inline-flex items-center gap-0.5 mt-0.5 max-w-[220px] truncate">
 {{ srv.service_url }}
 <svg class="w-2.5 h-2.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
 </a>
 </div>
 </div>
 </td>
 <td class="py-3 px-6"><span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[11px] font-medium">{{ srv.category }}</span></td>
 <td class="py-3 px-6">
 <div class="flex items-center gap-2">
 <div :class="['w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0', getAvatarColor(srv.tester_initials, srv.tester_name)]">{{ srv.tester_initials }}</div>
 <span class="text-slate-700 font-medium">{{ srv.tester_name }}</span>
 </div>
 </td>
 <td class="py-3 px-6">
 <span v-if="srv.status === 'normal'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
 <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Normal
 </span>
 <span v-else-if="srv.status === 'gangguan'" class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-rose-50 text-rose-700 border border-rose-100">
 <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Kendala
 </span>
 <span v-else class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-500 border border-slate-200">
 <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Belum Dicek
 </span>
 </td>
 <td class="py-3 px-6 text-slate-400">{{ srv.checked_at || '-' }}</td>
 <td class="py-3 px-6 text-slate-500 max-w-[200px] truncate">{{ srv.description || '-' }}</td>
 </tr>
 <tr v-if="filteredServices.length === 0">
 <td colspan="6" class="py-8 text-center text-slate-400 text-xs">Tidak ada layanan yang sesuai dengan filter.</td>
 </tr>
 </tbody>
 </table>
 </div>
 <ClientPagination :meta="paginationMeta" @update:page="currentPage = $event" @update:perPage="perPage = $event; currentPage = 1" class="px-6 py-4 border-t border-slate-100 bg-slate-50/50" />
 </div>

 </div>

  <!-- ════════════════════════════════════════════════════ -->
  <!-- Modal: Peringatan Deadline Melewati Batas            -->
  <!-- ════════════════════════════════════════════════════ -->
  <Teleport to="body">
  <Transition
   enter-active-class="transition-all duration-200 ease-out"
   enter-from-class="opacity-0"
   enter-to-class="opacity-100"
   leave-active-class="transition-all duration-150 ease-in"
   leave-from-class="opacity-100"
   leave-to-class="opacity-0"
  >
   <div v-if="showOverdueModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showOverdueModal = false"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm sm:max-w-md p-6 sm:p-7 z-10 text-center">
     
     <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-4 border border-amber-200 shadow-xs">
      <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
       <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
      </svg>
     </div>

     <h3 class="text-lg font-bold text-slate-800">Tidak Dapat Mengaktifkan Periode</h3>
     <p class="text-sm text-slate-500 mt-2.5 leading-relaxed mb-6">
      Tenggat waktu (deadline) untuk periode 
      <span class="font-semibold text-slate-800">"{{ period.title }}"</span> 
      telah melewati batas (<span class="font-bold text-rose-600">{{ period.deadline_formatted || period.deadline_date }}</span>).
      <br><br>
      Periode tidak dapat diaktifkan kembali saat tenggat waktu telah terlewat. Silakan 
      <span class="font-semibold text-slate-700">perbarui/edit tenggat waktu</span> terlebih dahulu.
     </p>

     <div class="flex gap-3">
      <button
       type="button"
       @click="showOverdueModal = false"
       class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
      >
       Batal
      </button>
      <Link
       :href="route('admin.periods.edit', period.id)"
       class="flex-1 py-2.5 rounded-xl bg-primary hover:bg-[#1d4ed8] text-white text-sm font-bold shadow-md shadow-primary/20 active:scale-95 transition-all cursor-pointer flex items-center justify-center gap-1.5"
      >
       <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
       </svg>
       Edit Deadline
      </Link>
     </div>
    </div>
   </div>
  </Transition>
 </Teleport>
 </AdminLayout>
</template>
