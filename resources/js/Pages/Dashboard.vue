<script setup>
import { Head, Link, usePage, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { computed, ref, onMounted, nextTick } from 'vue';
import { Chart, registerables } from 'chart.js';

// Register semua Chart.js components
Chart.register(...registerables);

const page = usePage();
const user = computed(() => page.props.auth.user);

// ─── Props dari DashboardController ───────────────────────────
const props = defineProps({
    // Admin props
    testerStats:           { type: Object, default: () => ({ total: 0, aktif: 0, nonaktif: 0 }) },
    periodStats:           { type: Object, default: () => ({ total: 0, proses: 0, selesai: 0 }) },
    serviceStats:          { type: Object, default: () => ({ total: 0, aktif: 0, nonaktif: 0 }) },
    categoryDistribution:  { type: Array,  default: () => [] },
    levelDistribution:     { type: Array,  default: () => [] },
    recentPeriods:         { type: Array,  default: () => [] },

    // Tester props
    activePeriod:     String,
    assignedServices: { type: Array,  default: () => [] },
    stats:            { type: Object, default: () => ({ checked: 0, pending: 0, issues: 0, need_confirmation: 0 }) },
});

// ─── Computed helpers ──────────────────────────────────────────
const testerAktifPct = computed(() => {
    if (!props.testerStats.total) return 0;
    return Math.round((props.testerStats.aktif / props.testerStats.total) * 100);
});
const serviceAktifPct = computed(() => {
    if (!props.serviceStats.total) return 0;
    return Math.round((props.serviceStats.aktif / props.serviceStats.total) * 100);
});
const currentMonthYear = computed(() =>
    props.activePeriod || new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date())
);

// ─── Chart refs ────────────────────────────────────────────────
const testerChartRef    = ref(null);
const periodChartRef    = ref(null);
const categoryChartRef  = ref(null);
const levelChartRef     = ref(null);

let testerChartInstance   = null;
let periodChartInstance   = null;
let categoryChartInstance = null;
let levelChartInstance    = null;

// ─── Color Palette ─────────────────────────────────────────────
const COLORS = {
    blue:       '#1d4ed8',
    blueLight:  '#3b82f6',
    bluePale:   '#bfdbfe',
    green:      '#059669',
    greenLight: '#34d399',
    red:        '#dc2626',
    redLight:   '#f87171',
    amber:      '#d97706',
    slate:      '#64748b',
    slateLight: '#cbd5e1',
    indigo:     '#4338ca',
    cyan:       '#0891b2',
    violet:     '#7c3aed',
    pink:       '#db2777',
    teal:       '#0d9488',
    orange:     '#ea580c',
    lime:       '#65a30d',
};

const CATEGORY_COLORS = [
    COLORS.blue, COLORS.green, COLORS.amber, COLORS.indigo,
    COLORS.cyan, COLORS.violet, COLORS.pink, COLORS.teal,
    COLORS.orange, COLORS.lime,
];

// ─── Pagination Kategori Layanan ──────────────────────────────
const categoryPage = ref(1);
const categoryPageSize = 10;

const totalCategories = computed(() => props.categoryDistribution?.length || 0);
const totalCategoryPages = computed(() => Math.ceil(totalCategories.value / categoryPageSize) || 1);

const categoryFromIndex = computed(() => {
    if (totalCategories.value === 0) return 0;
    return (categoryPage.value - 1) * categoryPageSize + 1;
});

const categoryToIndex = computed(() => {
    return Math.min(categoryPage.value * categoryPageSize, totalCategories.value);
});

const paginatedCategories = computed(() => {
    if (!props.categoryDistribution) return [];
    const start = (categoryPage.value - 1) * categoryPageSize;
    return props.categoryDistribution.slice(start, start + categoryPageSize);
});

const categoryPageNumbers = computed(() => {
    const pages = [];
    for (let i = 1; i <= totalCategoryPages.value; i++) {
        pages.push(i);
    }
    return pages;
});

// Tinggi chart konsisten: tetap sama di semua halaman saat total > 10
const categoryChartHeight = computed(() => {
    const itemsCount = Math.min(totalCategories.value, categoryPageSize);
    return Math.max(200, itemsCount * 36 + 40) + 'px';
});

function renderCategoryChart() {
    if (!categoryChartRef.value) return;
    if (categoryChartInstance) {
        categoryChartInstance.destroy();
        categoryChartInstance = null;
    }

    const currentItems = paginatedCategories.value;
    if (currentItems.length === 0) return;

    const catLabels = currentItems.map(c => c.category);
    const catData   = currentItems.map(c => c.total);
    const startIdx  = (categoryPage.value - 1) * categoryPageSize;
    const catColors = currentItems.map((_, i) => CATEGORY_COLORS[(startIdx + i) % CATEGORY_COLORS.length]);

    categoryChartInstance = new Chart(categoryChartRef.value, {
        type: 'bar',
        data: {
            labels: catLabels,
            datasets: [{
                label: 'Jumlah Layanan',
                data: catData,
                backgroundColor: catColors.map(c => c + 'cc'),
                borderColor: catColors,
                borderWidth: 1.5,
                borderRadius: 6,
                borderSkipped: false,
                maxBarThickness: 24,
            }],
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.parsed.x} layanan`,
                    },
                },
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 },
                    grid: { color: '#f1f5f9' },
                },
                y: {
                    grid: { display: false },
                    ticks: { font: { size: 12 } },
                },
            },
        },
    });
}

function setCategoryPage(page) {
    if (page < 1 || page > totalCategoryPages.value || page === categoryPage.value) return;
    categoryPage.value = page;
    nextTick(() => {
        renderCategoryChart();
    });
}

function initCharts() {
    // Chart 1 — Tester Doughnut
    if (testerChartRef.value && props.testerStats.total > 0) {
        if (testerChartInstance) testerChartInstance.destroy();
        testerChartInstance = new Chart(testerChartRef.value, {
            type: 'doughnut',
            data: {
                labels: ['Aktif', 'Nonaktif'],
                datasets: [{
                    data: [props.testerStats.aktif, props.testerStats.nonaktif],
                    backgroundColor: [COLORS.green, COLORS.slateLight],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 3,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 14, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.parsed} tester`,
                        },
                    },
                },
            },
        });
    }

    // Chart 2 — Period Doughnut
    if (periodChartRef.value && props.periodStats.total > 0) {
        if (periodChartInstance) periodChartInstance.destroy();
        periodChartInstance = new Chart(periodChartRef.value, {
            type: 'doughnut',
            data: {
                labels: ['Proses', 'Selesai'],
                datasets: [{
                    data: [props.periodStats.proses, props.periodStats.selesai],
                    backgroundColor: [COLORS.amber, COLORS.green],
                    borderColor: ['#fff', '#fff'],
                    borderWidth: 3,
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '70%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 14, font: { size: 12 }, usePointStyle: true, pointStyleWidth: 10 },
                    },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.label}: ${ctx.parsed} periode`,
                        },
                    },
                },
            },
        });
    }

    // Chart 3 — Kategori Horizontal Bar (dengan pagination 10 per halaman)
    if (categoryChartRef.value && props.categoryDistribution.length > 0) {
        renderCategoryChart();
    }

    // Chart 4 — Level Bar Chart
    if (levelChartRef.value) {
        if (levelChartInstance) levelChartInstance.destroy();
        const lvlLabels = props.levelDistribution && props.levelDistribution.length > 0
            ? props.levelDistribution.map(l => String(l.level).startsWith('Level') ? l.level : `Level ${l.level}`)
            : ['Level 1', 'Level 2', 'Level 3'];
        const lvlData   = props.levelDistribution && props.levelDistribution.length > 0
            ? props.levelDistribution.map(l => Number(l.total) || 0)
            : [0, 0, 0];

        levelChartInstance = new Chart(levelChartRef.value, {
            type: 'bar',
            data: {
                labels: lvlLabels,
                datasets: [{
                    label: 'Jumlah Layanan',
                    data: lvlData,
                    backgroundColor: [
                        COLORS.blue + 'cc',
                        COLORS.indigo + 'cc',
                        COLORS.cyan + 'cc',
                    ],
                    borderColor: [COLORS.blue, COLORS.indigo, COLORS.cyan],
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (ctx) => ` ${ctx.parsed.y} layanan`,
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1, precision: 0 },
                        grid: { color: '#f1f5f9' },
                    },
                    x: {
                        grid: { display: false },
                    },
                },
            },
        });
    }
}

onMounted(async () => {
    if (user.value?.role === 'admin') {
        await nextTick();
        initCharts();
    }
});

// ─── Status helpers ────────────────────────────────────────────
function statusLabel(status) {
    return status === 'selesai' ? 'Selesai' : 'Proses';
}
function statusClass(status) {
    return status === 'selesai'
        ? 'bg-emerald-50 text-emerald-700 border border-emerald-100'
        : 'bg-amber-50 text-amber-700 border border-amber-100';
}
function statusDotClass(status) {
    return status === 'selesai' ? 'bg-emerald-500' : 'bg-amber-400';
}
function progressColor(pct) {
    if (pct >= 100) return 'bg-emerald-500';
    if (pct >= 50)  return 'bg-blue-500';
    return 'bg-amber-400';
}

// ─── Tester Modal ──────────────────────────────────────────────
const showCheckModal  = ref(false);
const selectedService = ref(null);
const checkForm = useForm({ status: '', description: '' });

function openCheckModal(service) {
    selectedService.value = service;
    checkForm.status      = service.status === 'pending' ? 'normal' : service.status;
    checkForm.description = service.last_description || '';
    checkForm.clearErrors();
    showCheckModal.value  = true;
}
function submitCheck() {
    checkForm.post(route('tester.submit_check', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => { showCheckModal.value = false; },
    });
}
</script>

<template>
    <Head :title="user?.role === 'admin' ? 'Dashboard Admin - MAJADIGI CHECKER' : 'Dashboard Tester - MAJADIGI CHECKER'" />

    <AdminLayout>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- DASHBOARD ADMIN                                         -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-if="user?.role === 'admin'" class="space-y-6">

            <!-- ── Header Banner ─────────────────────────────────── -->
            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#1e3a8a] via-[#1d4ed8] to-[#0284c7] px-7 py-6 text-white shadow-lg">
                <div class="absolute -right-12 -top-12 w-56 h-56 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-28 bottom-0 w-40 h-40 rounded-full bg-cyan-400/20 blur-xl pointer-events-none"></div>
                <div class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/20 mb-2 text-[11px] font-bold tracking-widest uppercase text-cyan-200">
                            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            Sistem Aktif
                        </div>
                        <h1 class="text-2xl font-black tracking-tight">Dashboard</h1>
                        <p class="text-blue-100/85 text-sm mt-1">Ringkasan monitoring MAJADIGI Checker System</p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <Link :href="route('admin.users.index')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"/></svg>
                            Manajemen User
                        </Link>
                        <Link :href="route('admin.periods.index')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                            Periode
                        </Link>
                        <Link :href="route('admin.services.index')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-white/15 hover:bg-white/25 border border-white/20 text-xs font-semibold transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/></svg>
                            Layanan
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── ROW 1: KPI Cards ────────────────────────────── -->
            <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

                <!-- Total Tester -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full">Tester</span>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ testerStats.total }}</div>
                        <p class="text-xs text-slate-400 mt-1">Tester terdaftar</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="font-semibold text-emerald-600">{{ testerStats.aktif }} aktif</span>
                        <span class="text-slate-300">·</span>
                        <span>{{ testerStats.nonaktif }} nonaktif</span>
                    </div>
                </div>

                <!-- Total Periode -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">Periode</span>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ periodStats.total }}</div>
                        <p class="text-xs text-slate-400 mt-1">Periode pengecekan</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-amber-400 inline-block"></span>
                        <span class="font-semibold text-amber-600">{{ periodStats.proses }} proses</span>
                        <span class="text-slate-300">·</span>
                        <span>{{ periodStats.selesai }} selesai</span>
                    </div>
                </div>

                <!-- Total Layanan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-emerald-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Layanan</span>
                    </div>
                    <div>
                        <div class="text-3xl font-black text-slate-800 leading-none">{{ serviceStats.total }}</div>
                        <p class="text-xs text-slate-400 mt-1">Layanan digital terdaftar</p>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span>
                        <span class="font-semibold text-emerald-600">{{ serviceStats.aktif }} aktif</span>
                        <span class="text-slate-300">·</span>
                        <span>{{ serviceStats.nonaktif }} nonaktif</span>
                    </div>
                </div>

                <!-- % Tester Aktif -->
                <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl shadow-sm p-5 flex flex-col gap-3 text-white">
                    <div class="flex items-center justify-between">
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <span class="text-xs font-semibold text-blue-200 bg-white/10 px-2 py-0.5 rounded-full">Coverage</span>
                    </div>
                    <div>
                        <div class="text-3xl font-black leading-none">{{ serviceAktifPct }}%</div>
                        <p class="text-xs text-blue-200 mt-1">Layanan aktif tersedia</p>
                    </div>
                    <div class="w-full h-1.5 bg-white/20 rounded-full overflow-hidden">
                        <div class="h-full bg-white/80 rounded-full transition-all duration-700" :style="{ width: serviceAktifPct + '%' }"></div>
                    </div>
                </div>
            </div>

            <!-- ── ROW 2: Charts Status Tester & Periode ─────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Status Tester Chart -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-800">Status Tester</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Distribusi tester aktif & nonaktif</p>
                    </div>
                    <div v-if="testerStats.total > 0" class="relative h-52">
                        <canvas ref="testerChartRef"></canvas>
                    </div>
                    <div v-else class="h-52 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"/></svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Belum ada data tester</p>
                    </div>
                    <!-- Mini Stats -->
                    <div v-if="testerStats.total > 0" class="mt-4 flex items-center justify-center gap-6">
                        <div class="text-center">
                            <p class="text-lg font-black text-emerald-600">{{ testerStats.aktif }}</p>
                            <p class="text-xs text-slate-400">Aktif</p>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <p class="text-lg font-black text-slate-400">{{ testerStats.nonaktif }}</p>
                            <p class="text-xs text-slate-400">Nonaktif</p>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <p class="text-lg font-black text-blue-600">{{ testerAktifPct }}%</p>
                            <p class="text-xs text-slate-400">Rate Aktif</p>
                        </div>
                    </div>
                </div>

                <!-- Status Periode Chart -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-800">Status Periode Pengecekan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Distribusi periode proses & selesai</p>
                    </div>
                    <div v-if="periodStats.total > 0" class="relative h-52">
                        <canvas ref="periodChartRef"></canvas>
                    </div>
                    <div v-else class="h-52 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Belum ada periode pengecekan</p>
                    </div>
                    <!-- Mini Stats -->
                    <div v-if="periodStats.total > 0" class="mt-4 flex items-center justify-center gap-6">
                        <div class="text-center">
                            <p class="text-lg font-black text-amber-600">{{ periodStats.proses }}</p>
                            <p class="text-xs text-slate-400">Proses</p>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <p class="text-lg font-black text-emerald-600">{{ periodStats.selesai }}</p>
                            <p class="text-xs text-slate-400">Selesai</p>
                        </div>
                        <div class="w-px h-8 bg-slate-100"></div>
                        <div class="text-center">
                            <p class="text-lg font-black text-blue-600">{{ periodStats.total }}</p>
                            <p class="text-xs text-slate-400">Total</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── ROW 3: Distribusi Kategori Layanan ─────────────── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col justify-between">
                <div class="p-6 pb-2">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Distribusi Kategori Layanan</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Jumlah layanan per kategori (dari database)</p>
                        </div>
                        <span class="text-xs font-semibold text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full">
                            {{ totalCategories }} kategori
                        </span>
                    </div>
                    <div v-if="totalCategories > 0">
                        <div class="relative" :style="{ height: categoryChartHeight }">
                            <canvas ref="categoryChartRef"></canvas>
                        </div>
                    </div>
                    <div v-else class="h-40 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/></svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Belum ada data kategori layanan</p>
                        <p class="text-xs text-slate-300 mt-1">Tambahkan layanan dengan kategori di Master Layanan</p>
                    </div>
                </div>

                <!-- ── Pagination Kategori (hanya tampil jika kategori > 10) ── -->
                <div
                    v-if="totalCategories > categoryPageSize"
                    class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
                >
                    <p class="text-slate-500 font-medium">
                        Menampilkan <span class="font-bold text-slate-700">{{ categoryFromIndex }}</span> sampai <span class="font-bold text-slate-700">{{ categoryToIndex }}</span> dari <span class="font-bold text-slate-700">{{ totalCategories }}</span> data
                    </p>

                    <nav class="inline-flex shadow-sm rounded-xl overflow-hidden border border-slate-200 bg-white" aria-label="Pagination Kategori">
                        <!-- Previous Button -->
                        <button
                            type="button"
                            :disabled="categoryPage === 1"
                            @click="setCategoryPage(categoryPage - 1)"
                            class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold transition-colors border-r border-slate-200"
                            :class="categoryPage === 1 ? 'bg-slate-50 text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600 cursor-pointer'"
                        >
                            &laquo; Previous
                        </button>

                        <!-- Page Numbers -->
                        <button
                            v-for="p in categoryPageNumbers"
                            :key="p"
                            type="button"
                            @click="setCategoryPage(p)"
                            class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold transition-colors border-r border-slate-200 last:border-r-0"
                            :class="p === categoryPage ? 'z-10 bg-blue-50 text-blue-600 font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600 cursor-pointer'"
                        >
                            {{ p }}
                        </button>

                        <!-- Next Button -->
                        <button
                            type="button"
                            :disabled="categoryPage === totalCategoryPages"
                            @click="setCategoryPage(categoryPage + 1)"
                            class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold transition-colors"
                            :class="categoryPage === totalCategoryPages ? 'bg-slate-50 text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600 cursor-pointer'"
                        >
                            Next &raquo;
                        </button>
                    </nav>
                </div>
            </div>

            <!-- ── ROW 4: Level Layanan & Progress Periode ─────────── -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                <!-- Distribusi Level Layanan -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="mb-4">
                        <h3 class="text-sm font-bold text-slate-800">Distribusi Level Layanan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Jumlah layanan per level (1–3)</p>
                    </div>
                    <div class="relative h-52">
                        <canvas ref="levelChartRef"></canvas>
                    </div>
                    <!-- Level Summary -->
                    <div class="mt-4 grid grid-cols-3 gap-3">
                        <div v-for="item in levelDistribution" :key="item.level" class="text-center p-2.5 rounded-xl bg-slate-50/60 border border-slate-100">
                            <p class="text-base font-black text-slate-700">{{ item.total }}</p>
                            <p class="text-xs font-semibold text-slate-500">{{ item.level }}</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Periode Terbaru -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <h3 class="text-sm font-bold text-slate-800">Progress Periode Terbaru</h3>
                            <p class="text-xs text-slate-400 mt-0.5">8 periode terakhir</p>
                        </div>
                        <Link :href="route('admin.periods.index')" class="text-xs text-blue-600 hover:text-blue-800 font-semibold transition-colors">
                            Lihat Semua →
                        </Link>
                    </div>
                    <div v-if="recentPeriods.length > 0" class="space-y-3">
                        <div v-for="p in recentPeriods" :key="p.id" class="group">
                            <div class="flex items-center justify-between mb-1">
                                <div class="flex items-center gap-2 min-w-0">
                                    <span
                                        class="shrink-0 inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold"
                                        :class="statusClass(p.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(p.status)"></span>
                                        {{ statusLabel(p.status) }}
                                    </span>
                                    <span class="text-xs text-slate-600 font-medium truncate">{{ p.date_label }}</span>
                                </div>
                                <span class="text-xs text-slate-400 shrink-0 ml-2">{{ p.checked }}/{{ p.total_services }}</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all duration-700"
                                    :class="progressColor(p.progress_percent)"
                                    :style="{ width: p.progress_percent + '%' }"
                                ></div>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-0.5 text-right">{{ p.progress_percent }}% · {{ p.total_testers }} tester</p>
                        </div>
                    </div>
                    <div v-else class="h-52 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Belum ada periode pengecekan</p>
                    </div>
                </div>
            </div>

            <!-- ── ROW 5: Ringkasan Periode Terbaru ───────────────── -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Ringkasan Periode Terbaru</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Detail 8 periode pengecekan terakhir</p>
                    </div>
                    <Link :href="route('admin.periods.index')" class="text-xs text-blue-600 hover:text-blue-800 font-semibold transition-colors">
                        Kelola Periode →
                    </Link>
                </div>
                <div v-if="recentPeriods.length > 0" class="overflow-x-auto">
                    <table class="w-full text-sm min-w-[600px]">
                        <thead>
                            <tr class="bg-slate-50/60 border-b border-slate-100">
                                <th class="text-left px-6 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Periode</th>
                                <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="text-center px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Tester</th>
                                <th class="text-left px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider w-52">Progress</th>
                                <th class="text-center px-4 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Dicek</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            <tr v-for="p in recentPeriods" :key="p.id" class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3.5">
                                    <span class="font-semibold text-slate-800 text-sm">{{ p.date_label }}</span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold"
                                        :class="statusClass(p.status)"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass(p.status)"></span>
                                        {{ statusLabel(p.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="inline-flex items-center gap-1 text-sm font-semibold text-slate-700">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 100-8 4 4 0 000 8z"/></svg>
                                        {{ p.total_testers }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 w-52">
                                    <div class="flex items-center gap-2">
                                        <div class="flex-1 h-2 bg-slate-100 rounded-full overflow-hidden">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="progressColor(p.progress_percent)"
                                                :style="{ width: p.progress_percent + '%' }"
                                            ></div>
                                        </div>
                                        <span class="text-xs font-semibold text-slate-500 w-8 text-right">{{ p.progress_percent }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-center">
                                    <span class="text-sm font-semibold text-slate-700">{{ p.checked }}</span>
                                    <span class="text-xs text-slate-400">/{{ p.total_services }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="py-16 flex flex-col items-center justify-center text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-50 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                    </div>
                    <p class="text-sm font-medium text-slate-500">Belum ada periode pengecekan</p>
                    <p class="text-xs text-slate-400 mt-1">Buat periode baru di halaman Periode Pengecekan</p>
                    <Link :href="route('admin.periods.index')" class="mt-4 px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors">
                        Buat Periode
                    </Link>
                </div>
            </div>

        </div>
        <!-- ─── END ADMIN DASHBOARD ────────────────────────────────── -->


        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- DASHBOARD TESTER (tidak diubah)                         -->
        <!-- ═══════════════════════════════════════════════════════ -->
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

            <!-- 4 Quick Stats Status Cards -->
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
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6 sm:p-8 flex flex-col sm:flex-row items-center justify-between gap-6">
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-800">Layanan yang Ditugaskan</h3>
                    <p class="text-sm text-slate-500 mt-1 leading-relaxed max-w-2xl">
                        Daftar lengkap layanan yang harus Anda cek beserta fitur pencarian dan pelaporan kendala kini tersedia di halaman tersendiri.
                    </p>
                </div>
                <Link :href="route('tester.assigned-services.index')" class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-blue-600 text-white font-bold hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all group">
                    Buka Daftar Layanan
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </Link>
            </div>
        </div>

    </AdminLayout>
</template>
