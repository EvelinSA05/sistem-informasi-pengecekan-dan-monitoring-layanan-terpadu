<script setup>
import { Head, Link, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);

// Saat ini data layanan dan periode aktif belum didistribusikan / belum ada
// Variabel disiapkan secara dinamis dan ramah pengguna (user-friendly)
const activePeriod = null; // null jika belum ada periode aktif
const assignedServices = []; // kosong jika belum ada tugas layanan

// Format tanggal saat ini (misal: September 2026)
const currentMonthYear = new Intl.DateTimeFormat('id-ID', {
    month: 'long',
    year: 'numeric'
}).format(new Date());
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
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex items-center gap-4 opacity-65 cursor-not-allowed">
                    <div class="w-13 h-13 rounded-2xl bg-slate-100 flex items-center justify-center p-3.5">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-600">Periode Pengecekan</p>
                        <p class="text-xs text-slate-400 mt-1">Segera hadir</p>
                    </div>
                </div>

                <!-- Monitoring Layanan Card -->
                <div class="bg-white rounded-2xl border border-slate-200/90 shadow-sm p-6 flex items-center gap-4 opacity-65 cursor-not-allowed">
                    <div class="w-13 h-13 rounded-2xl bg-slate-100 flex items-center justify-center p-3.5">
                        <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2"/>
                            <rect x="2" y="3" width="20" height="14" rx="2"/>
                            <path stroke-linecap="round" d="M8 21h8M12 17v4"/>
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-bold text-slate-600">Monitoring Layanan</p>
                        <p class="text-xs text-slate-400 mt-1">Segera hadir</p>
                    </div>
                </div>
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
                        <div class="text-3xl font-black text-slate-800 leading-none">0</div>
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
                        <div class="text-3xl font-black text-slate-800 leading-none">0</div>
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
                        <div class="text-3xl font-black text-slate-800 leading-none">0</div>
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
                        <div class="text-3xl font-black text-slate-800 leading-none">0</div>
                        <p class="text-xs text-slate-400 mt-1">Perlu konfirmasi admin</p>
                    </div>
                </div>
            </div>

            <!-- Bagian Layanan yang Ditugaskan (User Friendly Empty State) -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8">
                <div class="flex items-center justify-between pb-5 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800">Layanan yang Ditugaskan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Daftar website dan aplikasi yang perlu Anda periksa</p>
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
                    <div
                        v-for="service in assignedServices"
                        :key="service.id"
                        class="py-4.5 flex items-center justify-between gap-4 group hover:bg-slate-50/60 rounded-xl px-2 transition-colors duration-150"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-sm">
                                🏛️
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate leading-snug">{{ service.name }}</h4>
                                <p class="text-xs text-slate-400 truncate mt-0.5">
                                    <span>{{ service.category }}</span> · 
                                    <a :href="service.url" target="_blank" class="hover:underline text-slate-400 hover:text-blue-600">
                                        {{ service.url }}
                                    </a>
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-semibold">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                {{ service.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
