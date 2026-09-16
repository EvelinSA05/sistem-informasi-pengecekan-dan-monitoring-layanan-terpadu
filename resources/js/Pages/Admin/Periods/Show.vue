<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    period:       { type: Object, required: true },
    distribution: { type: Array,  default: () => [] },
});

const isUpdatingStatus = ref(false);
const statusFilter = ref('all');
const searchQuery = ref('');

function toggleStatus() {
    const nextStatus = props.period.status === 'proses' ? 'selesai' : 'proses';
    isUpdatingStatus.value = true;
    router.put(route('admin.periods.update', props.period.id), {
        status: nextStatus,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isUpdatingStatus.value = false;
        }
    });
}

// ─── Filtered Services ───────────────────────────────────────────
const allServices = computed(() => {
    const list = [];
    props.distribution.forEach(group => {
        group.services.forEach(srv => {
            list.push({
                ...srv,
                tester_name: group.name,
                tester_bidang: group.bidang,
                tester_initials: group.initials,
            });
        });
    });
    return list;
});

const filteredServices = computed(() => {
    return allServices.value.filter(srv => {
        const matchesStatus = statusFilter.value === 'all' || srv.status === statusFilter.value;
        const q = searchQuery.value.toLowerCase().trim();
        const matchesSearch = !q
            || srv.service_name.toLowerCase().includes(q)
            || srv.category.toLowerCase().includes(q)
            || srv.tester_name.toLowerCase().includes(q);
        return matchesStatus && matchesSearch;
    });
});

// ─── Avatar & Icon Helpers ───────────────────────────────────────
const testerColorPalette = {
    'SD': 'bg-pink-600',
    'AF': 'bg-blue-600',
    'AP': 'bg-blue-600',
    'DR': 'bg-rose-600',
    'BS': 'bg-violet-600',
    'TK': 'bg-amber-600',
};
function getTesterAvatarColor(initials, name) {
    if (testerColorPalette[initials]) return testerColorPalette[initials];
    const colors = ['bg-pink-600', 'bg-blue-600', 'bg-rose-600', 'bg-violet-600', 'bg-emerald-600', 'bg-amber-600', 'bg-cyan-600'];
    const idx = (name?.charCodeAt(0) ?? 0) % colors.length;
    return colors[idx];
}

const serviceIcons = {
    globe: `<svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="12" cy="12" r="10"/><path stroke-linecap="round" d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
    </svg>`,
    megaphone: `<svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"/>
    </svg>`,
    box: `<svg class="w-4 h-4 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
    </svg>`,
    chart: `<svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
    </svg>`,
    currency: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
    </svg>`,
    document: `<svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
    </svg>`,
    certificate: `<svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
    </svg>`,
    academic: `<svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>
    </svg>`,
    archive: `<svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/>
    </svg>`,
    users: `<svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
    </svg>`,
    database: `<svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <ellipse cx="12" cy="5" rx="9" ry="3"/><path stroke-linecap="round" d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path stroke-linecap="round" d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/>
    </svg>`,
    'shopping-cart': `<svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path stroke-linecap="round" stroke-linejoin="round" d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/>
    </svg>`,
};

function getServiceIcon(iconKey) {
    return serviceIcons[iconKey] || serviceIcons['globe'];
}
</script>

<template>
    <Head :title="`Detail ${period.title} - MAJADIGI CHECKER`" />

    <AdminLayout
        title="Periode Pengecekan"
        subtitle="Atur periode dan distribusi layanan"
    >
        <!-- ── Top Header Navigation & Action Toolbar ──────────── -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
            <div class="flex items-center gap-4">
                <Link
                    :href="route('admin.periods.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-700 transition-colors group"
                >
                    <span class="text-sm transition-transform group-hover:-translate-x-0.5">←</span>
                    <span>Kembali</span>
                </Link>
                <div>
                    <div class="flex items-center gap-3">
                        <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                            Detail Periode: {{ period.title }}
                        </h2>
                        <!-- Status Badge -->
                        <span
                            v-if="period.status === 'proses'"
                            class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase bg-[#1e3a8a] text-white shadow-xs"
                        >
                            <span class="w-1.5 h-1.5 rounded-full bg-cyan-300 animate-pulse"></span>
                            PROSES
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase bg-slate-100 border border-slate-200/80 text-slate-600"
                        >
                            <svg class="w-3 h-3 text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                            SELESAI
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-1 font-medium">
                        Dibuat pada {{ period.created_at_label }} oleh {{ period.creator_name }}
                    </p>
                </div>
            </div>

            <!-- Export Buttons & Actions Toolbar -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <!-- Export Excel -->
                <a
                    :href="route('admin.periods.export.excel', period.id)"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow-xs hover:shadow-sm transition-all duration-150"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Export Excel</span>
                </a>

                <!-- Export PDF / Cetak Laporan -->
                <a
                    :href="route('admin.periods.export.pdf', period.id)"
                    target="_blank"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-xs hover:shadow-sm transition-all duration-150"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    <span>Export PDF</span>
                </a>

                <!-- Edit Periode Button -->
                <Link
                    :href="route('admin.periods.edit', period.id)"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-all duration-150"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/>
                    </svg>
                    <span>Edit Periode</span>
                </Link>

                <!-- Quick Toggle Status -->
                <button
                    @click="toggleStatus"
                    :disabled="isUpdatingStatus"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-xs font-bold shadow-xs hover:shadow-sm transition-all duration-150"
                >
                    <span>{{ period.status === 'proses' ? 'Tandai Selesai' : 'Buka Kembali' }}</span>
                </button>
            </div>
        </div>

        <!-- ── Infographic Executive KPI Cards ──────────────────── -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- Total Layanan -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Layanan</p>
                    <div class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-slate-800 mt-2">{{ period.total_services }}</p>
                <p class="text-xs text-slate-400 mt-1 font-medium">Layanan Digital Jatim</p>
            </div>

            <!-- Normal -->
            <div class="bg-white rounded-3xl border border-emerald-100 bg-gradient-to-br from-white to-emerald-50/20 p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Layanan Normal</p>
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-emerald-700 mt-2">{{ period.normal_services }}</p>
                <p class="text-xs text-emerald-600 mt-1 font-medium">Berjalan Optimal</p>
            </div>

            <!-- Kendala -->
            <div class="bg-white rounded-3xl border border-rose-100 bg-gradient-to-br from-white to-rose-50/20 p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Terkendala</p>
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-rose-700 mt-2">{{ period.issues_services }}</p>
                <p class="text-xs text-rose-500 mt-1 font-medium">Perlu Penanganan</p>
            </div>

            <!-- Progress Pengecekan -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Progress Cek</p>
                    <span class="text-xs font-bold text-blue-700">{{ period.checked_services }}/{{ period.total_services }}</span>
                </div>
                <p class="text-3xl font-extrabold text-slate-800 mt-2">{{ period.progress_percent }}%</p>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden mt-2">
                    <div
                        class="h-full rounded-full transition-all duration-500"
                        :class="period.progress_percent === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                        :style="{ width: `${period.progress_percent}%` }"
                    ></div>
                </div>
            </div>
        </div>

        <!-- ── Tester Workload Infographic Summary ──────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm mb-6">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
                DISTRIBUSI & BEBAN KERJA TESTER
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    v-for="group in distribution"
                    :key="group.tester_id"
                    class="p-4 rounded-2xl bg-slate-50/70 border border-slate-200/70 flex items-center justify-between"
                >
                    <div class="flex items-center gap-3">
                        <div
                            :class="[
                                'w-10 h-10 rounded-2xl flex items-center justify-center text-white text-xs font-bold shadow-xs',
                                getTesterAvatarColor(group.initials, group.name)
                            ]"
                        >
                            {{ group.initials }}
                        </div>
                        <div>
                            <p class="text-sm font-bold text-slate-800 leading-tight">{{ group.name }}</p>
                            <p class="text-xs text-slate-400 font-medium">{{ group.email }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-extrabold text-slate-800">
                            {{ group.checked_services }}/{{ group.total_services }}
                        </p>
                        <p class="text-[11px] text-slate-400 font-medium">layanan selesai</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Detailed Service Checklist Table ─────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
            <!-- Table Toolbar -->
            <div class="px-6 py-4 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-800">Daftar Layanan</span>
                    <span class="text-xs font-semibold text-slate-400">({{ filteredServices.length }} dari {{ allServices.length }})</span>
                </div>

                <!-- Filter & Search -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Status Filter Tabs -->
                    <div class="inline-flex p-1 bg-slate-100 rounded-xl text-xs font-semibold text-slate-600">
                        <button
                            @click="statusFilter = 'all'"
                            :class="['px-2.5 py-1 rounded-lg transition-colors', statusFilter === 'all' ? 'bg-white text-slate-800 shadow-2xs font-bold' : 'hover:text-slate-800']"
                        >
                            Semua
                        </button>
                        <button
                            @click="statusFilter = 'normal'"
                            :class="['px-2.5 py-1 rounded-lg transition-colors', statusFilter === 'normal' ? 'bg-white text-emerald-700 shadow-2xs font-bold' : 'hover:text-slate-800']"
                        >
                            Normal
                        </button>
                        <button
                            @click="statusFilter = 'gangguan'"
                            :class="['px-2.5 py-1 rounded-lg transition-colors', statusFilter === 'gangguan' ? 'bg-white text-rose-700 shadow-2xs font-bold' : 'hover:text-slate-800']"
                        >
                            Kendala
                        </button>
                        <button
                            @click="statusFilter = 'pending'"
                            :class="['px-2.5 py-1 rounded-lg transition-colors', statusFilter === 'pending' ? 'bg-white text-slate-700 shadow-2xs font-bold' : 'hover:text-slate-800']"
                        >
                            Belum Dicek
                        </button>
                    </div>

                    <!-- Search Input -->
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari layanan / tester..."
                        class="px-3.5 py-1.5 text-xs bg-slate-50 border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white w-48 transition-all"
                    />
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/40 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-3.5 px-6">LAYANAN</th>
                            <th class="py-3.5 px-6">KATEGORI</th>
                            <th class="py-3.5 px-6">PENANGGUNG JAWAB</th>
                            <th class="py-3.5 px-6">STATUS</th>
                            <th class="py-3.5 px-6">WAKTU CEK</th>
                            <th class="py-3.5 px-6">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-xs">
                        <tr
                            v-for="srv in filteredServices"
                            :key="srv.id"
                            class="hover:bg-slate-50/70 transition-colors"
                        >
                            <!-- Layanan -->
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-xl bg-slate-100 flex items-center justify-center shrink-0 border border-slate-200/50" v-html="getServiceIcon(srv.icon)">
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-800 leading-tight">{{ srv.service_name }}</p>
                                        <a
                                            v-if="srv.service_url"
                                            :href="srv.service_url"
                                            target="_blank"
                                            class="text-[11px] text-blue-500 hover:underline inline-flex items-center gap-0.5 mt-0.5"
                                        >
                                            <span>Buka link</span>
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </td>

                            <!-- Kategori -->
                            <td class="py-3.5 px-6">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-semibold">
                                    {{ srv.category }}
                                </span>
                            </td>

                            <!-- Penanggung Jawab -->
                            <td class="py-3.5 px-6">
                                <div class="flex items-center gap-2">
                                    <div
                                        :class="[
                                            'w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold',
                                            getTesterAvatarColor(srv.tester_initials, srv.tester_name)
                                        ]"
                                    >
                                        {{ srv.tester_initials }}
                                    </div>
                                    <span class="font-semibold text-slate-700">{{ srv.tester_name }}</span>
                                </div>
                            </td>

                            <!-- Status Cek -->
                            <td class="py-3.5 px-6">
                                <span
                                    v-if="srv.status === 'normal'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold text-[10.5px] bg-emerald-50 text-emerald-700 border border-emerald-200/80"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                    Normal
                                </span>
                                <span
                                    v-else-if="srv.status === 'gangguan'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold text-[10.5px] bg-rose-50 text-rose-700 border border-rose-200/80"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                                    Kendala
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full font-bold text-[10.5px] bg-slate-100 text-slate-500 border border-slate-200"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                                    Belum Dicek
                                </span>
                            </td>

                            <!-- Waktu Cek -->
                            <td class="py-3.5 px-6 text-slate-400 font-medium">
                                {{ srv.checked_at || '-' }}
                            </td>

                            <!-- Keterangan -->
                            <td class="py-3.5 px-6 text-slate-600 font-medium max-w-xs truncate">
                                {{ srv.description || '-' }}
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="filteredServices.length === 0">
                            <td colspan="6" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada layanan yang sesuai dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>
