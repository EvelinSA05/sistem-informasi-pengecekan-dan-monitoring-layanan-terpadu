<script setup>
import { Head, Link, router, usePage, useForm } from '@inertiajs/vue3';
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

    // Tester Monitoring Center props
    activePeriod:       { type: String, default: null },
    periodInfo:         { type: Object, default: null },
    stats:              { type: Object, default: () => ({ total_assigned: 0, checked: 0, pending: 0, issues: 0, normal: 0, need_confirmation: 0, progress_percent: 0, remaining_count: 0 }) },
    statusDistribution: { type: Array,  default: () => [] },
    actionRequired:     { type: Array,  default: () => [] },
    priorityTasks:      { type: Array,  default: () => [] },
    recentActivities:   { type: Array,  default: () => [] },
    quickActions:       { type: Array,  default: () => [] },
    flash:              { type: Object, default: () => ({ success: null, error: null }) },
});

// ─── Computed helpers ──────────────────────────────────────────
const testerAktifPct = computed(() => {
    if (!props.testerStats?.total) return 0;
    return Math.round((props.testerStats.aktif / props.testerStats.total) * 100);
});
const serviceAktifPct = computed(() => {
    if (!props.serviceStats?.total) return 0;
    return Math.round((props.serviceStats.aktif / props.serviceStats.total) * 100);
});
const currentMonthYear = computed(() =>
    props.periodInfo?.month_year || props.activePeriod || new Intl.DateTimeFormat('id-ID', { month: 'long', year: 'numeric' }).format(new Date())
);

// ─── Chart refs (Admin & Tester) ───────────────────────────────
const testerChartRef       = ref(null);
const periodChartRef       = ref(null);
const categoryChartRef     = ref(null);
const levelChartRef        = ref(null);
const testerStatusChartRef = ref(null);

let testerChartInstance       = null;
let periodChartInstance       = null;
let categoryChartInstance     = null;
let levelChartInstance        = null;
let testerStatusChartInstance = null;

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

// ─── Admin Pagination Kategori ────────────────────────────────
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

// ─── Tester Status Doughnut Chart ─────────────────────────────
function renderTesterStatusChart() {
    if (!testerStatusChartRef.value) return;
    if (testerStatusChartInstance) {
        testerStatusChartInstance.destroy();
        testerStatusChartInstance = null;
    }

    if (!props.statusDistribution || props.statusDistribution.length === 0 || props.stats.total_assigned === 0) {
        return;
    }

    const labels = props.statusDistribution.map(item => item.status);
    const data   = props.statusDistribution.map(item => item.total);
    const colors = props.statusDistribution.map(item => item.color);

    testerStatusChartInstance = new Chart(testerStatusChartRef.value, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: colors,
                borderColor: ['#fff', '#fff', '#fff', '#fff'],
                borderWidth: 3,
                hoverOffset: 6,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 12, font: { size: 11, weight: 'bold' }, usePointStyle: true, pointStyleWidth: 8 },
                },
                tooltip: {
                    callbacks: {
                        label: (ctx) => ` ${ctx.label}: ${ctx.parsed} layanan`,
                    },
                },
            },
        },
    });
}

function initCharts() {
    if (user.value?.role === 'admin') {
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

        // Chart 3 — Kategori Horizontal Bar
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
    } else if (user.value?.role === 'tester') {
        renderTesterStatusChart();
    }
}

onMounted(async () => {
    await nextTick();
    initCharts();
});

// ─── Admin Status helpers ──────────────────────────────────────
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

// ─── Tester Monitoring Toast & Modal ───────────────────────────
const toast = ref(props.flash?.success || '');
const toastType = ref('success');
let toastTimer = null;

function showToast(msg, type = 'success') {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = msg;
    toastType.value = type;
    toastTimer = setTimeout(() => { toast.value = ''; }, 4000);
}

const isEditMode = computed(() => {
    return selectedService.value && selectedService.value.status !== 'pending';
});

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
        onSuccess: () => {
            showCheckModal.value = false;
            showToast(isEditMode.value ? 'Hasil pengujian berhasil diperbarui.' : 'Hasil pengecekan berhasil disimpan.');
            nextTick(() => {
                renderTesterStatusChart();
            });
        },
    });
}
</script>

<template>
    <Head :title="user?.role === 'admin' ? 'Dashboard Admin - MAJADIGI CHECKER' : 'Dashboard Monitoring Tester - MAJADIGI CHECKER'" />

    <AdminLayout>

        <!-- ═══════════════════════════════════════════════════════ -->
        <!-- DASHBOARD ADMIN (100% UTUH)                              -->
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

                <!-- ── Pagination Kategori ── -->
                <div
                    v-if="totalCategories > categoryPageSize"
                    class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
                >
                    <p class="text-slate-500 font-medium">
                        Menampilkan <span class="font-bold text-slate-700">{{ categoryFromIndex }}</span> sampai <span class="font-bold text-slate-700">{{ categoryToIndex }}</span> dari <span class="font-bold text-slate-700">{{ totalCategories }}</span> data
                    </p>

                    <nav class="inline-flex shadow-sm rounded-xl overflow-hidden border border-slate-200 bg-white" aria-label="Pagination Kategori">
                        <button
                            type="button"
                            :disabled="categoryPage === 1"
                            @click="setCategoryPage(categoryPage - 1)"
                            class="relative inline-flex items-center px-3.5 py-1.5 text-xs font-semibold transition-colors border-r border-slate-200"
                            :class="categoryPage === 1 ? 'bg-slate-50 text-slate-300 cursor-not-allowed' : 'text-slate-600 hover:bg-slate-50 hover:text-blue-600 cursor-pointer'"
                        >
                            &laquo; Previous
                        </button>

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
                            Lihat Semua
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
                        Kelola Periode
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
        <!-- DASHBOARD TESTER (MONITORING CENTER)                     -->
        <!-- ═══════════════════════════════════════════════════════ -->
        <div v-else class="space-y-6 max-w-7xl mx-auto">

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
                        'fixed top-5 right-5 z-[120] flex items-center justify-between gap-3 px-4.5 py-3 rounded-2xl shadow-xl text-sm font-semibold border backdrop-blur-md max-w-md',
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

            <!-- ── 1. Hero Welcome & Active Period Banner ────────── -->
            <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-[#1e40af] via-[#1d4ed8] to-[#0284c7] text-white p-6 sm:p-8 shadow-xl shadow-blue-900/15 border border-blue-400/20">
                <div class="absolute -right-16 -top-16 w-64 h-64 rounded-full bg-white/10 blur-2xl pointer-events-none"></div>
                <div class="absolute right-32 bottom-0 w-48 h-48 rounded-full bg-cyan-400/20 blur-xl pointer-events-none"></div>
                <div class="absolute left-1/3 -bottom-10 w-40 h-40 rounded-full bg-blue-300/10 blur-xl pointer-events-none"></div>

                <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                    <div class="max-w-2xl">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 backdrop-blur-md border border-white/25 mb-3 text-[11px] font-bold tracking-wider uppercase text-cyan-200 shadow-xs">
                            <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
                            Monitoring Center Tester
                        </div>
                        <h2 class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight leading-tight">
                            Selamat Datang, {{ user?.name }}! 👋
                        </h2>
                        <p class="text-blue-100/90 text-sm sm:text-base mt-2 font-normal leading-relaxed">
                            Pantau ringkasan progres, status, dan peringatan pengujian layanan digital yang ditugaskan kepada Anda.
                        </p>
                    </div>

                    <!-- Period Info Card -->
                    <div class="shrink-0 flex items-center">
                        <div class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 shadow-lg text-left sm:text-right">
                            <div class="flex items-center sm:justify-end gap-2 mb-1">
                                <span class="w-2 h-2 rounded-full" :class="periodInfo?.status === 'selesai' ? 'bg-emerald-400' : 'bg-amber-300 animate-pulse'"></span>
                                <p class="text-[11px] font-bold text-cyan-200 tracking-widest uppercase">
                                    {{ periodInfo?.status === 'selesai' ? 'Periode Selesai' : 'Periode Berjalan' }}
                                </p>
                            </div>
                            <p class="text-lg sm:text-xl font-extrabold text-white capitalize leading-snug">
                                {{ currentMonthYear }}
                            </p>
                            <div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-blue-100/90 flex-wrap">
                                <span v-if="periodInfo?.date" class="inline-flex items-center gap-1.5 bg-black/20 px-3 py-1 rounded-xl border border-white/15 shadow-inner">
                                    <svg class="w-3.5 h-3.5 text-cyan-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
                                    {{ periodInfo.period_title }}
                                </span>
                                <span v-if="periodInfo && periodInfo.status === 'proses'" class="inline-flex items-center gap-1 bg-amber-400/25 text-amber-200 px-3 py-1 rounded-xl border border-amber-300/35 shadow-inner">
                                    <span v-if="periodInfo.days_remaining > 0">⏱️ {{ periodInfo.days_remaining }} hari lagi</span>
                                    <span v-else-if="periodInfo.days_remaining === 0">⏱️ Hari ini</span>
                                    <span v-else>⚠️ Lewat target</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── 2. 5 KPI Summary Cards (Menjawab 5 Pertanyaan Utama) ── -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 sm:gap-4">

                <!-- 1. Total Ditugaskan -->
                <Link
                    :href="route('tester.services.index')"
                    class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-indigo-300 transition-all duration-200 flex flex-col justify-between group cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Total Tugas</span>
                        <div class="w-9 h-9 rounded-xl bg-indigo-50 border border-indigo-100 text-indigo-600 flex items-center justify-center transition-transform group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-slate-800 leading-none tracking-tight">{{ props.stats.total_assigned }}</div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">layanan ditugaskan</p>
                    </div>
                </Link>

                <!-- 2. Sudah Dicek -->
                <Link
                    :href="route('tester.services.index', { status: 'normal' })"
                    class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-emerald-300 transition-all duration-200 flex flex-col justify-between group cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider">Sudah Dicek</span>
                        <div class="w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center transition-transform group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <rect x="3" y="3" width="18" height="18" rx="3" stroke="currentColor" stroke-width="2"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 12l3 3 5-5"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-emerald-600 leading-none tracking-tight">{{ props.stats.checked }}</div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">{{ props.stats.progress_percent }}% dari tugas</p>
                    </div>
                </Link>

                <!-- 3. Belum Dicek -->
                <Link
                    :href="route('tester.services.index', { status: 'pending' })"
                    class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-blue-300 transition-all duration-200 flex flex-col justify-between group cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-blue-700 uppercase tracking-wider">Belum Dicek</span>
                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center transition-transform group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-blue-600 leading-none tracking-tight">{{ props.stats.pending }}</div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">perlu ditindak</p>
                    </div>
                </Link>

                <!-- 4. Terkendala -->
                <Link
                    :href="route('tester.services.index', { status: 'gangguan' })"
                    class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs hover:shadow-md hover:border-rose-300 transition-all duration-200 flex flex-col justify-between group cursor-pointer"
                >
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-rose-700 uppercase tracking-wider">Terkendala</span>
                        <div class="w-9 h-9 rounded-xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center transition-transform group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-rose-600 leading-none tracking-tight">{{ props.stats.issues }}</div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">perlu perhatian</p>
                    </div>
                </Link>

                <!-- 5. Need Confirmation -->
                <div class="relative overflow-hidden rounded-2xl bg-white border border-slate-200/80 p-5 shadow-xs hover:shadow-md transition-all duration-200 flex flex-col justify-between col-span-2 sm:col-span-1 group">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider">Konfirmasi</span>
                        <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center transition-transform group-hover:scale-105">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="text-3xl font-black text-amber-600 leading-none tracking-tight">{{ props.stats.need_confirmation }}</div>
                        <p class="text-xs text-slate-400 mt-1 font-medium">review admin</p>
                    </div>
                </div>
            </div>

            <!-- ── Panduan Pengujian Layanan & Akses Layanan Ditugaskan (Dari main) ── -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
                <div class="flex items-start gap-4">
                    <div class="w-10 h-10 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center shrink-0 shadow-xs mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Panduan Pengujian Layanan</h3>
                        <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed max-w-3xl">
                            Pastikan untuk memeriksa tautan (URL) dari setiap layanan yang ditugaskan kepada Anda. Catat dan laporkan kendala yang Anda temukan secara jelas pada kolom deskripsi. Jika tidak ada kendala, simpan dengan status <strong class="text-emerald-700 font-bold">"NORMAL"</strong>. Jika terdapat instruksi khusus pada layanan tersebut, mohon perhatikan dan laksanakan instruksi tersebut.
                        </p>
                    </div>
                </div>
                <Link
                    :href="route('tester.services.index')"
                    class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all group cursor-pointer"
                >
                    <span>Buka Layanan Ditugaskan</span>
                    <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </Link>
            </div>

            <!-- ── 3. ROW 1: Progress Pengujian Saya & Distribusi Status Chart ── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                <!-- Progress Pengujian Card (7 Cols) -->
                <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                            <div>
                                <h3 class="text-base sm:text-lg font-bold text-slate-800">Progress Pengujian Saya</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Persentase penyelesaian layanan yang ditugaskan pada periode aktif</p>
                            </div>
                            <span class="inline-flex items-center gap-1.5 self-start sm:self-auto px-3.5 py-1.5 rounded-full text-xs font-black shadow-2xs" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200'">
                                <span class="w-2 h-2 rounded-full" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-500' : 'bg-blue-500'"></span>
                                {{ props.stats.progress_percent }}% Selesai
                            </span>
                        </div>

                        <!-- Progress Bar with Gradient -->
                        <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden p-0.5 mb-2.5 border border-slate-200/60">
                            <div
                                class="h-full rounded-full transition-all duration-700 ease-out shadow-xs"
                                :class="props.stats.progress_percent >= 100 ? 'bg-gradient-to-r from-emerald-500 to-teal-500' : (props.stats.progress_percent >= 50 ? 'bg-gradient-to-r from-blue-500 to-emerald-500' : 'bg-gradient-to-r from-amber-400 to-blue-500')"
                                :style="{ width: props.stats.progress_percent + '%' }"
                            ></div>
                        </div>

                        <div class="flex items-center justify-between text-xs text-slate-500 font-medium flex-wrap gap-2">
                            <p>
                                <strong class="font-bold text-slate-800">{{ props.stats.checked }}</strong> dari <strong class="font-bold text-slate-800">{{ props.stats.total_assigned }}</strong> layanan telah diuji.
                            </p>
                            <p v-if="props.stats.remaining_count > 0" class="text-amber-700 font-semibold">
                                {{ props.stats.remaining_count }} layanan masih perlu diuji
                            </p>
                            <p v-else-if="props.stats.total_assigned > 0" class="text-emerald-600 font-semibold">
                                Seluruh tugas telah selesai diuji 🎉
                            </p>
                        </div>
                    </div>

                    <!-- Mini Stat Badges -->
                    <div class="grid grid-cols-4 gap-2.5 sm:gap-3 mt-6 pt-5 border-t border-slate-100 text-center">
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Ditugaskan</p>
                            <p class="text-base sm:text-lg font-black text-slate-700 mt-0.5">{{ props.stats.total_assigned }}</p>
                        </div>
                        <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/70">
                            <p class="text-[11px] text-emerald-700 font-bold uppercase tracking-wider">Normal</p>
                            <p class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">{{ props.stats.normal }}</p>
                        </div>
                        <div class="p-3 rounded-2xl bg-rose-50/70 border border-rose-200/70">
                            <p class="text-[11px] text-rose-700 font-bold uppercase tracking-wider">Kendala</p>
                            <p class="text-base sm:text-lg font-black text-rose-700 mt-0.5">{{ props.stats.issues }}</p>
                        </div>
                        <div class="p-3 rounded-2xl bg-blue-50/70 border border-blue-200/70">
                            <p class="text-[11px] text-blue-700 font-bold uppercase tracking-wider">Pending</p>
                            <p class="text-base sm:text-lg font-black text-blue-700 mt-0.5">{{ props.stats.pending }}</p>
                        </div>
                    </div>
                </div>

                <!-- Distribusi Status Doughnut Chart (5 Cols) -->
                <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Distribusi Status Pengujian</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Proporsi hasil pengujian periode ini</p>
                            </div>
                            <span class="text-xs font-bold text-slate-500 bg-slate-50 border border-slate-200 px-3 py-1 rounded-full">
                                Real-Time
                            </span>
                        </div>
                    </div>

                    <div v-if="props.stats.total_assigned > 0" class="relative h-48 my-auto">
                        <canvas ref="testerStatusChartRef"></canvas>
                    </div>
                    <div v-else class="h-48 flex flex-col items-center justify-center text-center my-auto text-slate-400">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-center mb-2">
                            <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/></svg>
                        </div>
                        <p class="text-xs font-medium">Belum ada data penugasan periode ini</p>
                    </div>

                    <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs font-medium text-slate-500">
                        <span>Total: <strong class="text-slate-700">{{ props.stats.total_assigned }}</strong> layanan</span>
                        <Link :href="route('tester.services.index')" class="text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1">
                            Ke Workspace →
                        </Link>
                    </div>
                </div>
            </div>


            <!-- ── 4. ROW 2: "Perlu Tindakan" & "Aksi Cepat" ────────── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                <!-- Section Perlu Tindakan (7 Cols) -->
                <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center font-bold">
                                    <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-base font-bold text-slate-800">Perlu Tindakan</h3>
                                    <p class="text-xs text-slate-400">Peringatan dan hal yang membutuhkan perhatian Anda</p>
                                </div>
                            </div>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                                {{ props.actionRequired.length }} Item
                            </span>
                        </div>

                        <!-- Actionable Alerts List -->
                        <div v-if="props.actionRequired.length > 0" class="space-y-3">
                            <div
                                v-for="(action, i) in props.actionRequired"
                                :key="i"
                                class="p-4 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                :class="action.severity === 'danger' ? 'bg-rose-50/60 border-rose-200 text-rose-900' : (action.severity === 'urgent' ? 'bg-amber-50/80 border-amber-300 text-amber-950' : 'bg-blue-50/60 border-blue-200 text-blue-950')"
                            >
                                <div class="flex items-start gap-3 min-w-0">
                                    <div
                                        class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs"
                                        :class="action.severity === 'danger' ? 'bg-rose-600 text-white' : (action.severity === 'urgent' ? 'bg-amber-500 text-white' : 'bg-blue-600 text-white')"
                                    >
                                        {{ i + 1 }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-xs sm:text-sm font-bold truncate">{{ action.title }}</h4>
                                            <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-white/80 border border-black/5 shadow-2xs">{{ action.badge }}</span>
                                        </div>
                                        <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ action.description }}</p>
                                    </div>
                                </div>

                                <Link
                                    :href="action.action_url"
                                    class="shrink-0 self-start sm:self-center px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
                                    :class="action.severity === 'danger' ? 'bg-rose-600 hover:bg-rose-700 text-white' : (action.severity === 'urgent' ? 'bg-amber-600 hover:bg-amber-700 text-white' : 'bg-blue-600 hover:bg-blue-700 text-white')"
                                >
                                    {{ action.action_label }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </Link>
                            </div>
                        </div>

                        <!-- All Good State -->
                        <div v-else class="py-8 px-4 rounded-2xl bg-emerald-50/50 border border-emerald-100 flex flex-col items-center justify-center text-center">
                            <div class="w-12 h-12 rounded-2xl bg-emerald-100 text-emerald-600 flex items-center justify-center mb-2 font-bold shadow-xs">
                                <svg class="w-6 h-6 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h4 class="text-sm font-extrabold text-emerald-800">Semua Tugas Dalam Kondisi Baik</h4>
                            <p class="text-xs text-emerald-600 mt-1">Belum ada tindakan atau kendala mendesak yang perlu dilakukan saat ini.</p>
                        </div>
                    </div>
                </div>

                <!-- Section Aksi Cepat / Shortcuts (5 Cols) -->
                <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="pb-3.5 border-b border-slate-100 mb-4">
                            <h3 class="text-base font-bold text-slate-800">Aksi Cepat</h3>
                            <p class="text-xs text-slate-400">Pintasan menuju halaman kerja pengujian</p>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <Link
                                v-for="action in props.quickActions"
                                :key="action.title"
                                :href="action.url"
                                class="p-4 rounded-2xl border border-slate-200/80 bg-slate-50/50 hover:bg-white hover:border-blue-300 hover:shadow-xs transition-all flex flex-col justify-between group"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <span
                                        class="w-8 h-8 rounded-xl flex items-center justify-center font-bold text-xs shadow-2xs border"
                                        :class="action.color === 'rose' ? 'bg-rose-50 border-rose-200 text-rose-600' : (action.color === 'emerald' ? 'bg-emerald-50 border-emerald-200 text-emerald-600' : (action.color === 'indigo' ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-blue-50 border-blue-200 text-blue-600'))"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                                    </span>
                                    <span class="text-xs font-black text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded-lg shadow-2xs">
                                        {{ action.count }}
                                    </span>
                                </div>
                                <div>
                                    <h4 class="text-xs font-extrabold text-slate-800 group-hover:text-blue-600 transition-colors">{{ action.title }}</h4>
                                    <p class="text-[10px] text-slate-400 mt-0.5 truncate">{{ action.subtitle }}</p>
                                </div>
                            </Link>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Workspace: Layanan Ditugaskan</span>
                        <Link :href="route('tester.services.index')" class="text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1">
                            Buka Lengkap →
                        </Link>
                    </div>
                </div>
            </div>

            <!-- ── 5. ROW 3: "Tugas Prioritas" & "Aktivitas Terbaru" ── -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

                <!-- Tugas Prioritas (7 Cols) -->
                <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Tugas Prioritas</h3>
                                <p class="text-xs text-slate-400">5 layanan prioritas yang memerlukan pengujian segera</p>
                            </div>
                            <Link
                                :href="route('tester.services.index')"
                                class="text-xs text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1 transition-colors"
                            >
                                Lihat Semua ({{ props.stats.total_assigned }}) →
                            </Link>
                        </div>

                        <div v-if="props.priorityTasks.length > 0" class="space-y-3">
                            <div
                                v-for="(task, idx) in props.priorityTasks"
                                :key="task.id"
                                class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/70 hover:bg-blue-50/30 hover:border-blue-200 transition-all flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <span class="w-6 h-6 rounded-lg bg-slate-200/80 text-slate-600 text-xs font-bold flex items-center justify-center shrink-0">
                                        {{ idx + 1 }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h4 class="text-xs sm:text-sm font-bold text-slate-800 truncate">{{ task.name }}</h4>
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-600">{{ task.category }}</span>
                                            <span v-if="task.level" class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-blue-50 border border-blue-200 text-blue-700">{{ task.level }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
                                            <a v-if="task.url" :href="task.url" target="_blank" class="text-blue-500 hover:underline truncate max-w-[220px]">
                                                {{ task.url }}
                                            </a>
                                            <span v-else>-</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="shrink-0 flex items-center gap-2">
                                    <span
                                        class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
                                        :class="task.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (task.status === 'gangguan' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
                                    >
                                        {{ task.status === 'normal' ? 'NORMAL' : (task.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
                                    </span>
                                    <button
                                        v-if="task.status === 'pending'"
                                        type="button"
                                        @click="openCheckModal(task)"
                                        class="inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl font-bold text-xs bg-blue-600 hover:bg-blue-700 text-white shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer border border-blue-600"
                                        title="Lakukan Pengecekan Layanan"
                                    >
                                        <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Cek
                                    </button>
                                    <button
                                        v-else
                                        type="button"
                                        @click="openCheckModal(task)"
                                        class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl font-semibold text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 shadow-2xs transition-all active:scale-95 cursor-pointer"
                                        title="Ubah Hasil Pengecekan"
                                    >
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        Ubah
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-10 text-center text-slate-400">
                            <p class="text-xs">Belum ada penugasan layanan untuk periode ini.</p>
                        </div>
                    </div>
                </div>

                <!-- Aktivitas Terbaru (5 Cols) -->
                <div class="lg:col-span-5 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Aktivitas Terbaru</h3>
                                <p class="text-xs text-slate-400">5 riwayat pengujian terakhir yang Anda lakukan</p>
                            </div>
                            <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
                                {{ props.recentActivities.length }} Riwayat
                            </span>
                        </div>

                        <div v-if="props.recentActivities.length > 0" class="space-y-3">
                            <div
                                v-for="act in props.recentActivities"
                                :key="act.id"
                                class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/70 hover:bg-slate-50 transition-colors flex items-start justify-between gap-3"
                            >
                                <div class="flex items-start gap-2.5 min-w-0">
                                    <div
                                        class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 mt-0.5 text-xs font-bold border shadow-2xs"
                                        :class="act.status === 'normal' ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'"
                                    >
                                        <svg v-if="act.status === 'normal'" class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                        <svg v-else class="w-3.5 h-3.5 stroke-[3]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01"/></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <h4 class="text-xs sm:text-sm font-bold text-slate-800 truncate">{{ act.service_name }}</h4>
                                        <p v-if="act.description" class="text-xs text-slate-600 mt-0.5 italic line-clamp-1">
                                            "{{ act.description }}"
                                        </p>
                                        <p v-else class="text-[11px] text-slate-400 mt-0.5">Tanpa catatan tambahan.</p>
                                    </div>
                                </div>

                                <div class="shrink-0 text-right">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="act.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200'"
                                    >
                                        {{ act.status === 'normal' ? 'NORMAL' : 'KENDALA' }}
                                    </span>
                                    <p class="text-[10px] text-slate-400 mt-1 font-medium">{{ act.checked_at_human || act.checked_at }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-else class="py-10 text-center text-slate-400">
                            <p class="text-xs">Belum ada riwayat aktivitas pengujian tercatat.</p>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Pemeriksaan berkala</span>
                        <Link :href="route('tester.services.index', { status: 'normal' })" class="text-blue-600 hover:text-blue-800 font-bold inline-flex items-center gap-1">
                            Lihat Semua Hasil →
                        </Link>
                    </div>
                </div>
            </div>
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
                                :class="isEditMode ? 'bg-indigo-50 border-indigo-200 text-indigo-600' : 'bg-blue-50 border-blue-200 text-blue-600'"
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
                                        class="px-2.5 py-0.5 rounded-full bg-blue-50 border border-blue-200 text-blue-700 font-bold text-[11px]"
                                    >
                                        {{ selectedService.level }}
                                    </span>
                                </div>
                                <div v-if="selectedService.url">
                                    <a
                                        :href="selectedService.url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-xs transition-all"
                                    >
                                        Buka URL Layanan
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                    </a>
                                </div>
                            </div>

                            <div v-if="selectedService.url" class="flex items-center gap-1.5 text-slate-500 pt-0.5">
                                <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                <a :href="selectedService.url" target="_blank" class="text-blue-600 hover:underline font-mono truncate text-[11px]">
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
                                    <span v-if="checkForm.status === 'gangguan'" class="text-rose-500 font-normal lowercase">(disarankan)</span>
                                    <span v-else class="text-slate-400 font-normal lowercase">(opsional)</span>
                                </label>
                                <textarea
                                    v-model="checkForm.description"
                                    rows="3"
                                    class="w-full rounded-xl bg-slate-50/70 border border-slate-200 text-xs focus:bg-white focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 p-3 leading-relaxed placeholder:text-slate-400 outline-none transition-all"
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
                            class="px-5 py-2 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all disabled:opacity-70 flex items-center gap-1.5 cursor-pointer active:scale-95"
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
