<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

// Props dari DashboardController
const props = defineProps({
    activePeriod: String,
    assignedServices: { type: Array, default: () => [] },
    stats: { type: Object, default: () => ({ checked: 0, pending: 0, issues: 0, need_confirmation: 0 }) }
});

const currentMonthYear = computed(() => props.activePeriod || new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date()));

// State untuk Modal Pengecekan
const showCheckModal = ref(false);
const selectedService = ref(null);

const checkForm = useForm({
    status: '',
    description: ''
});

function openCheckModal(service) {
    selectedService.value = service;
    checkForm.status = service.status === 'pending' ? 'normal' : service.status;
    checkForm.description = service.last_description || '';
    checkForm.clearErrors();
    showCheckModal.value = true;
}

function submitCheck() {
    checkForm.post(route('tester.submit_check', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCheckModal.value = false;
        }
    });
}
</script>

<template>
    <Head :title="user?.role === 'admin' ? 'Dashboard Admin - MAJADIGI CHECKER' : 'Dashboard Tester - MAJADIGI CHECKER'" />

    <AdminLayout>
        <!-- ════════════════════════════════════════════════════ -->
        <!-- DASHBOARD ADMIN                                      -->
        <!-- ════════════════════════════════════════════════════ -->
        <div v-if="user?.role === 'admin'" class="space-y-6">
            <!-- Quick Access Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                <!-- Manajemen User Card -->
                <Link
                    :href="route('admin.users.index')"
                    class="group bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex items-center gap-4 hover:shadow-md hover:border-blue-300 transition-all duration-200"
                >
                    <div class="w-13 h-13 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:bg-blue-600 transition-colors duration-200 p-3.5">
                        <svg class="w-6 h-6 text-blue-600 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8zm6-4a3 3 0 11-6 0 3 3 0 016 0zM3 16a3 3 0 116 0"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-800 group-hover:text-blue-700 transition-colors">Manajemen User</p>
                        <p class="text-xs text-slate-400 mt-1">Kelola data tester terdaftar</p>
                    </div>
                </Link>

                <!-- Periode Pengecekan Card -->
                <Link
                    :href="route('admin.periods.index')"
                    class="group bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex items-center gap-4 hover:shadow-md hover:border-blue-300 transition-all duration-200"
                >
                    <div class="w-13 h-13 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:bg-blue-600 transition-colors duration-200 p-3.5">
                        <svg class="w-6 h-6 text-blue-600 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-800 group-hover:text-blue-700 transition-colors">Periode Pengecekan</p>
                        <p class="text-xs text-slate-400 mt-1">Atur periode dan distribusi layanan</p>
                    </div>
                </Link>

                <!-- Master Layanan Card -->
                <Link
                    :href="route('admin.services.index')"
                    class="group bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex items-center gap-4 hover:shadow-md hover:border-blue-300 transition-all duration-200"
                >
                    <div class="w-13 h-13 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center group-hover:bg-blue-600 transition-colors duration-200 p-3.5">
                        <svg class="w-6 h-6 text-blue-600 group-hover:text-white transition-colors duration-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2"/>
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <path stroke-linecap="round" d="M8 21h8M12 17v4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-800 group-hover:text-blue-700 transition-colors">Master Layanan</p>
                        <p class="text-xs text-slate-400 mt-1">Kelola data layanan digital</p>
                    </div>
                </Link>
            </div>

            <!-- Info Card -->
            <div class="relative overflow-hidden bg-gradient-to-r from-[#1e3a8a] via-[#1d4ed8] to-[#2563eb] rounded-3xl p-8 text-white shadow-xl">
                <div class="relative z-10 max-w-2xl">
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 mb-3 text-xs font-bold tracking-wider uppercase text-cyan-200">
                        <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                        Admin Panel
                    </div>
                    <h2 class="text-2xl font-black mb-2">MAJADIGI Checker System</h2>
                    <p class="text-blue-100/85 text-sm leading-relaxed">
                        Platform monitoring terpusat untuk memantau, mendistribusikan, dan memvalidasi mutu layanan digital Pemerintah Provinsi Jawa Timur.
                    </p>
                    <Link
                        :href="route('admin.users.index')"
                        class="inline-flex items-center gap-2 mt-6 px-5 py-2.5 rounded-xl bg-white text-blue-900 text-sm font-bold shadow-lg shadow-blue-950/20 hover:bg-blue-50 transition-all duration-200"
                    >
                        Kelola Akun Tester
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- DASHBOARD TESTER (CLEAN, MODERN & USER FRIENDLY)     -->
        <!-- ════════════════════════════════════════════════════ -->
        <div v-else class="space-y-6 max-w-7xl mx-auto">
            
            <!-- Banner Sambutan & Status Periode -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1e40af] via-[#1d4ed8] to-[#0284c7] text-white p-7 sm:p-8 shadow-xl shadow-blue-900/10">
                <!-- Background decorative shapes -->
                <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-32 bottom-0 w-48 h-48 rounded-full bg-cyan-400/20 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 mb-2.5 text-[11px] font-bold tracking-wider uppercase text-cyan-200">
                            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            Tester Layanan
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                            Selamat Datang, {{ user?.name }}! 👋
                        </h2>
                        <p class="text-blue-100/90 text-sm mt-1.5 font-normal max-w-xl leading-relaxed">
                            Pantau dan uji kualitas layanan digital Majadigi Jawa Timur secara berkala sesuai penugasan yang diberikan.
                        </p>
                    </div>

                    <!-- Badge Periode Saat Ini -->
                    <div class="shrink-0 flex items-center">
                        <div class="px-5 py-3 rounded-2xl bg-white/15 backdrop-blur-md border border-white/20 text-center">
                            <p class="text-[10px] font-bold text-cyan-200 tracking-widest uppercase">Periode Berjalan</p>
                            <p class="text-base font-extrabold text-white mt-0.5 capitalize">{{ currentMonthYear }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4 Quick Stats Status Cards (Seragam dengan warna Majadigi Palette) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <!-- 1. Sudah Dicek -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Sudah Dicek</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12l3 3 5-5"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ props.stats.checked }}</div>
                        <p class="text-xs text-slate-400 mt-1">Layanan terselesaikan</p>
                    </div>
                </div>

                <!-- 2. Belum Dicek -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Belum Dicek</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ props.stats.pending }}</div>
                        <p class="text-xs text-slate-400 mt-1">Menunggu pengujian</p>
                    </div>
                </div>

                <!-- 3. Terkendala -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Terkendala</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ props.stats.issues }}</div>
                        <p class="text-xs text-slate-400 mt-1">Mengalami kendala/error</p>
                    </div>
                </div>

                <!-- 4. Need Confirmation -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-all duration-200 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Need Confirmation</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ props.stats.need_confirmation }}</div>
                        <p class="text-xs text-slate-400 mt-1">Perlu konfirmasi admin</p>
                    </div>
                </div>
            </div>

            <!-- Panduan Pengujian -->
            <div class="bg-blue-50/50 rounded-3xl border border-blue-100 p-6 sm:p-8">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-2xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800">Panduan Pengujian Layanan</h3>
                        <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">
                            Pastikan untuk memeriksa tautan (URL) dari setiap layanan yang ditugaskan kepada Anda. Catat dan laporkan kendala yang Anda temukan secara jelas pada kolom deskripsi. Jika tidak ada kendala, simpan dengan status "NORMAL". Jika terdapat instruksi khusus pada layanan tersebut, mohon perhatikan dan laksanakan instruksi tersebut.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian Layanan yang Ditugaskan -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between pb-5 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800">Layanan yang Ditugaskan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Klik layanan untuk menguji dan melaporkan statusnya</p>
                    </div>
                </div>

                <!-- Empty State bila belum ada penugasan -->
                <div v-if="assignedServices.length === 0" class="py-14 px-4 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">Belum Ada Layanan yang Ditugaskan</h4>
                    <p class="text-sm text-slate-400 max-w-md mt-1 leading-relaxed">
                        Saat ini admin belum membagikan daftar layanan untuk periode ini. Anda akan mendapatkan notifikasi begitu penugasan siap dicek.
                    </p>
                </div>

                <!-- List Layanan jika sudah ada penugasan -->
                <div v-else class="divide-y divide-slate-100">
                    <button
                        v-for="service in props.assignedServices"
                        :key="service.id"
                        @click="openCheckModal(service)"
                        class="w-full py-4.5 flex items-center justify-between gap-4 group hover:bg-slate-50/60 rounded-xl px-2 transition-colors duration-150 text-left"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-sm transition-transform group-hover:scale-105">
                                🏛️
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate leading-snug group-hover:text-blue-600 transition-colors">{{ service.name }}</h4>
                                <p class="text-xs text-slate-400 truncate mt-0.5">
                                    <span>{{ service.category }}</span>
                                    <span v-if="service.url"> · {{ service.url }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="service.status === 'normal' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : (service.status === 'gangguan' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-600')"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="service.status === 'normal' ? 'bg-emerald-400' : (service.status === 'gangguan' ? 'bg-rose-400' : 'bg-slate-400')"></span>
                                {{ service.status === 'normal' ? 'NORMAL' : (service.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
                            </span>
                            <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                </div>
            </div>

        </div>

        <!-- ── Modal Pengecekan Layanan (Tester) ────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showCheckModal && selectedService" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Cek Layanan</h3>
                            <p class="text-xs text-slate-500">{{ selectedService.name }}</p>
                        </div>
                        <button @click="showCheckModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    
                    <div class="p-6 overflow-y-auto space-y-5">
                        <!-- Informasi Layanan -->
                        <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">URL Layanan</p>
                                <a v-if="selectedService.url" :href="selectedService.url" target="_blank" class="text-sm font-medium text-blue-600 hover:underline inline-flex items-center gap-1.5">
                                    {{ selectedService.url }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <p v-else class="text-sm text-slate-500">-</p>
                            </div>
                            <div v-if="selectedService.deskripsi_layanan">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Deskripsi Layanan</p>
                                <p class="text-sm text-slate-700">{{ selectedService.deskripsi_layanan }}</p>
                            </div>
                            <div v-if="selectedService.tester_instruction" class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Instruksi Khusus Tester
                                </p>
                                <p class="text-sm text-amber-900 whitespace-pre-wrap">{{ selectedService.tester_instruction }}</p>
                            </div>
                        </div>

                        <!-- Form Pelaporan -->
                        <form @submit.prevent="submitCheck" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Hasil Pengecekan <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="checkForm.status" value="normal" class="peer sr-only">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition-colors">
                                            <p class="font-bold text-sm">NORMAL</p>
                                            <p class="text-xs mt-0.5 opacity-80">Layanan Berfungsi</p>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="checkForm.status" value="gangguan" class="peer sr-only">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 transition-colors">
                                            <p class="font-bold text-sm">KENDALA</p>
                                            <p class="text-xs mt-0.5 opacity-80">Ada Error/Gangguan</p>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="checkForm.errors.status" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.status }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Catatan / Keterangan Kendala</label>
                                <textarea v-model="checkForm.description" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan secara singkat kendala yang ditemukan, jika ada..."></textarea>
                                <p v-if="checkForm.errors.description" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.description }}</p>
                            </div>
                        </form>
                    </div>
                    
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showCheckModal = false" class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="button" @click="submitCheck" :disabled="checkForm.processing" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all disabled:opacity-70 flex items-center gap-2">
                            <svg v-if="checkForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Hasil
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
