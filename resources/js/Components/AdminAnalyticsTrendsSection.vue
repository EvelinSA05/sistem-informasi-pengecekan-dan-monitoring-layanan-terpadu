<script setup>
import { ref, computed, onMounted, onUnmounted, watch, nextTick } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const props = defineProps({
    periodTrends: {
        type: Array,
        default: () => [],
    },
    topTroubledServices: {
        type: Array,
        default: () => [],
    },
    troubledServicesDetail: {
        type: Array,
        default: () => [],
    },
    periodServicesDetail: {
        type: Array,
        default: () => [],
    },
});

const drillLevel = ref(1); // 1 = Grafik 1 (Tren Periode) | 2 = Grafik 2 (Distribusi OPD) | 3 = Detail Layanan
const drillDownSearch = ref('');
const drillDownPeriodId = ref('all');
const drillDownStatus = ref('all'); // 'all' | 'normal' | 'gangguan' | 'pending'
const drillDownCategory = ref('');
const drillDownServiceName = ref('');
const analyticsCardRef = ref(null);
const opdPage = ref(1);
const opdPerPage = 6;

const opdChartRef = ref(null);
let opdChartInstance = null;

const statusLabelMap = {
    all: 'Semua Status',
    normal: 'Normal',
    gangguan: 'Terkendala',
    pending: 'Belum Dicek',
};

const selectedStatusLabel = computed(() => statusLabelMap[drillDownStatus.value] || 'Semua Status');

async function openDrillDown(options = {}) {
    drillDownPeriodId.value = options.periodId ? String(options.periodId) : 'all';
    drillDownStatus.value = options.status || (options.serviceName ? 'gangguan' : 'all');
    drillDownCategory.value = options.category || '';
    drillDownServiceName.value = options.serviceName || '';
    drillDownSearch.value = options.serviceName || '';
    drillLevel.value = options.serviceName ? 3 : (options.level || 2);
    await nextTick();
    analyticsCardRef.value?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function goToStep(step) {
    if (step === 1 || step === 'root') {
        drillDownPeriodId.value = 'all';
        drillDownStatus.value = 'all';
        drillDownCategory.value = '';
        drillDownServiceName.value = '';
        drillDownSearch.value = '';
        drillLevel.value = 1;
        return;
    }
    if (step === 2 || step === 'opd') {
        drillDownCategory.value = '';
        drillDownServiceName.value = '';
        drillDownSearch.value = '';
        drillLevel.value = 2;
    }
}

function selectOpdBranch(category) {
    drillDownCategory.value = category;
    drillDownServiceName.value = '';
    drillDownSearch.value = '';
    drillLevel.value = 3;
}

const selectedPeriodLabel = computed(() => {
    if (drillDownPeriodId.value === 'all') return 'Semua Periode';
    const found = (props.periodTrends || []).find(p => String(p.id) === drillDownPeriodId.value);
    return found?.date_label || 'Periode Terpilih';
});

const baseDrillDownItems = computed(() => {
    if (props.periodServicesDetail && props.periodServicesDetail.length > 0) {
        return props.periodServicesDetail;
    }
    return (props.troubledServicesDetail || []).map(item => ({ ...item, status: 'gangguan' }));
});

const periodScopedItems = computed(() => {
    const items = baseDrillDownItems.value;
    return items.filter(item => {
        const matchPeriod = drillDownPeriodId.value === 'all' || String(item.period_id) === drillDownPeriodId.value;
        const matchStatus = drillDownStatus.value === 'all' || item.status === drillDownStatus.value;
        return matchPeriod && matchStatus;
    });
});

const opdBranchStats = computed(() => {
    const counts = new Map();
    periodScopedItems.value.forEach(item => {
        const cat = item.category || 'Umum';
        const existing = counts.get(cat) || { category: cat, total: 0, normal: 0, gangguan: 0, pending: 0 };
        existing.total += 1;
        if (item.status === 'normal') existing.normal += 1;
        else if (item.status === 'gangguan') existing.gangguan += 1;
        else existing.pending += 1;
        counts.set(cat, existing);
    });
    return Array.from(counts.values()).sort((a, b) => b.total - a.total);
});

const opdTotalPages = computed(() => Math.max(1, Math.ceil(opdBranchStats.value.length / opdPerPage)));

const paginatedOpdBranches = computed(() => {
    const start = (opdPage.value - 1) * opdPerPage;
    return opdBranchStats.value.slice(start, start + opdPerPage);
});

const filteredDrillDownItems = computed(() => {
    const items = periodScopedItems.value;
    const q = drillDownSearch.value.trim().toLowerCase();
    return items.filter(item => {
        const matchCategory = !drillDownCategory.value || (item.category || 'Lainnya') === drillDownCategory.value;
        const matchQuery = !q
            || (item.service_name || '').toLowerCase().includes(q)
            || (item.category || '').toLowerCase().includes(q)
            || (item.tester_name || '').toLowerCase().includes(q)
            || (item.description || '').toLowerCase().includes(q);
        return matchCategory && matchQuery;
    });
});

const trendChartRef = ref(null);
let trendChartInstance = null;

const latestPeriod = computed(() => {
    if (!props.periodTrends?.length) return null;
    return props.periodTrends[props.periodTrends.length - 1];
});

const previousPeriod = computed(() => {
    if (!props.periodTrends || props.periodTrends.length < 2) return null;
    return props.periodTrends[props.periodTrends.length - 2];
});

const averageHealthRate = computed(() => {
    const checkedPeriods = (props.periodTrends || []).filter(p => (p.checked ?? 0) > 0);
    if (!checkedPeriods.length) return 0;
    const sum = checkedPeriods.reduce((acc, p) => acc + (p.health_rate ?? 0), 0);
    return Math.round(sum / checkedPeriods.length);
});

function buildTrendChart() {
    if (!trendChartRef.value || !props.periodTrends?.length) return;

    if (trendChartInstance) {
        trendChartInstance.destroy();
        trendChartInstance = null;
    }

    const labels = props.periodTrends.map(p => p.short_label || p.date_label);
    const normalData = props.periodTrends.map(p => p.normal ?? 0);
    const gangguanData = props.periodTrends.map(p => p.gangguan ?? 0);
    const pendingData = props.periodTrends.map(p => p.pending ?? 0);

    trendChartInstance = new Chart(trendChartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Normal',
                    data: normalData,
                    backgroundColor: '#059669',
                    borderRadius: 6,
                    barPercentage: 0.72,
                    categoryPercentage: 0.68,
                },
                {
                    label: 'Terkendala',
                    data: gangguanData,
                    backgroundColor: '#e11d48',
                    borderRadius: 6,
                    barPercentage: 0.72,
                    categoryPercentage: 0.68,
                },
                {
                    label: 'Belum Dicek',
                    data: pendingData,
                    backgroundColor: '#cbd5e1',
                    borderRadius: 6,
                    barPercentage: 0.72,
                    categoryPercentage: 0.68,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            onClick: (_event, elements) => {
                if (elements && elements.length > 0) {
                    const idx = elements[0].index;
                    const dsIdx = elements[0].datasetIndex;
                    const statusByDataset = ['normal', 'gangguan', 'pending'];
                    const p = props.periodTrends?.[idx];
                    if (p) {
                        openDrillDown({
                            periodId: p.id,
                            status: statusByDataset[dsIdx] || 'all',
                        });
                    }
                }
            },
            onHover: (event, elements) => {
                if (event?.native?.target) {
                    event.native.target.style.cursor = elements?.length ? 'pointer' : 'default';
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: false,
                        useBorderRadius: true,
                        borderRadius: 4,
                        font: {
                            size: 11,
                            weight: '600',
                        },
                        color: '#475569',
                    },
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 11, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 8,
                    cornerRadius: 8,
                    yAlign: 'bottom',
                    caretPadding: 6,
                    callbacks: {
                        afterBody: (items) => {
                            const idx = items?.[0]?.dataIndex;
                            const p = props.periodTrends?.[idx];
                            if (!p) return '';
                            return `Normal: ${p.health_rate ?? 0}% (${p.checked}/${p.total} dicek)`;
                        },
                    },
                },
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                    },
                    ticks: {
                        font: { size: 11, weight: '600' },
                        color: '#64748b',
                    },
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0,
                        font: { size: 11 },
                        color: '#94a3b8',
                    },
                    grid: {
                        color: '#f1f5f9',
                    },
                },
            },
        },
    });
}

function buildOpdChart() {
    if (opdChartInstance) {
        opdChartInstance.destroy();
        opdChartInstance = null;
    }
    if (drillLevel.value !== 2 || !opdChartRef.value) return;

    const topBranches = opdBranchStats.value.slice(0, 10);
    if (!topBranches.length) return;

    const labels = topBranches.map(b => b.category.length > 28 ? `${b.category.slice(0, 26)}...` : b.category);
    const datasets = drillDownStatus.value === 'normal'
        ? [{ label: 'Normal', data: topBranches.map(b => b.normal), backgroundColor: '#059669', borderRadius: 6, barPercentage: 0.72 }]
        : drillDownStatus.value === 'gangguan'
            ? [{ label: 'Terkendala', data: topBranches.map(b => b.gangguan), backgroundColor: '#e11d48', borderRadius: 6, barPercentage: 0.72 }]
            : drillDownStatus.value === 'pending'
                ? [{ label: 'Belum Dicek', data: topBranches.map(b => b.pending), backgroundColor: '#94a3b8', borderRadius: 6, barPercentage: 0.72 }]
                : [
                    { label: 'Normal', data: topBranches.map(b => b.normal), backgroundColor: '#059669', borderRadius: 6, barPercentage: 0.72 },
                    { label: 'Terkendala', data: topBranches.map(b => b.gangguan), backgroundColor: '#e11d48', borderRadius: 6, barPercentage: 0.72 },
                    { label: 'Belum Dicek', data: topBranches.map(b => b.pending), backgroundColor: '#cbd5e1', borderRadius: 6, barPercentage: 0.72 },
                ];

    opdChartInstance = new Chart(opdChartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets,
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            onClick: (_event, elements) => {
                if (elements && elements.length > 0) {
                    const idx = elements[0].index;
                    const branch = topBranches[idx];
                    if (branch) {
                        selectOpdBranch(branch.category);
                    }
                }
            },
            onHover: (event, elements) => {
                if (event?.native?.target) {
                    event.native.target.style.cursor = elements?.length ? 'pointer' : 'default';
                }
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 8,
                        boxHeight: 8,
                        usePointStyle: false,
                        useBorderRadius: true,
                        borderRadius: 4,
                        font: { size: 11, weight: '600' },
                        color: '#475569',
                    },
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    titleFont: { size: 11, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 8,
                    cornerRadius: 8,
                    yAlign: 'bottom',
                    caretPadding: 6,
                    callbacks: {
                        title: (items) => {
                            const idx = items?.[0]?.dataIndex;
                            return topBranches[idx]?.category || '';
                        },
                    },
                },
            },
            scales: {
                x: {
                    stacked: true,
                    beginAtZero: true,
                    ticks: { precision: 0, font: { size: 11 }, color: '#94a3b8' },
                    grid: { color: '#f1f5f9' },
                },
                y: {
                    stacked: true,
                    grid: { display: false },
                    ticks: { font: { size: 11, weight: '600' }, color: '#475569' },
                },
            },
        },
    });
}

onMounted(async () => {
    await nextTick();
    buildTrendChart();
});

watch(
    () => props.periodTrends,
    async () => {
        await nextTick();
        buildTrendChart();
    },
    { deep: true }
);

watch(
    [drillLevel, drillDownPeriodId, drillDownStatus],
    async ([level]) => {
        opdPage.value = 1;
        await nextTick();
        if (level === 1) {
            if (opdChartInstance) {
                opdChartInstance.destroy();
                opdChartInstance = null;
            }
            buildTrendChart();
        } else if (level === 2) {
            if (trendChartInstance) {
                trendChartInstance.destroy();
                trendChartInstance = null;
            }
            buildOpdChart();
        } else {
            if (trendChartInstance) {
                trendChartInstance.destroy();
                trendChartInstance = null;
            }
            if (opdChartInstance) {
                opdChartInstance.destroy();
                opdChartInstance = null;
            }
        }
    }
);

onUnmounted(() => {
    if (trendChartInstance) {
        trendChartInstance.destroy();
        trendChartInstance = null;
    }
    if (opdChartInstance) {
        opdChartInstance.destroy();
        opdChartInstance = null;
    }
});
</script>

<template>
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        <!-- Kiri: Grafik Tren & Cabang Drill-Down In-Place (7 Kolom) -->
        <div ref="analyticsCardRef" class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col">
            <div>
                <!-- Header Card + Breadcrumb Drill-Down In-Place -->
                <div class="mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-primary border border-blue-100 text-[10px] font-bold uppercase tracking-wider whitespace-nowrap">
                            Analytics Antar-Periode
                        </span>

                        <!-- Kanan Header: Badge Delta (Level 1) atau Tombol Kembali (Level 2 & 3) -->
                        <div class="shrink-0 flex items-center gap-2">
                            <template v-if="drillLevel === 1 && latestPeriod && previousPeriod">
                                <span
                                    v-if="latestPeriod.issue_delta < 0"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold whitespace-nowrap"
                                >
                                    &darr; {{ Math.abs(latestPeriod.issue_delta) }} kendala vs periode lalu
                                </span>
                                <span
                                    v-else-if="latestPeriod.issue_delta > 0"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-bold whitespace-nowrap"
                                >
                                    &uarr; +{{ latestPeriod.issue_delta }} kendala vs periode lalu
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-[11px] font-bold whitespace-nowrap"
                                >
                                    = Stabil vs periode lalu
                                </span>
                            </template>
                            <template v-else-if="drillLevel > 1">
                                <button
                                    type="button"
                                    @click="goToStep(drillLevel === 3 ? 2 : 1)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer whitespace-nowrap"
                                >
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                    </svg>
                                    <span>{{ drillLevel === 3 ? 'Kembali ke Grafik 2' : 'Kembali ke Grafik 1' }}</span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <!-- Breadcrumb Drill-Down (Level 2 & 3) -->
                    <div v-if="drillLevel > 1" class="flex flex-wrap items-center gap-1.5 mb-2">
                        <button
                            type="button"
                            @click="goToStep(1)"
                            class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold cursor-pointer transition-colors"
                        >
                            Grafik 1
                        </button>
                        <span class="text-slate-300 text-xs">&rsaquo;</span>
                        <button
                            type="button"
                            @click="goToStep(2)"
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold border transition-colors cursor-pointer"
                            :class="drillLevel === 2
                                ? 'bg-primary text-white border-primary'
                                : 'bg-white text-slate-600 border-slate-200 hover:border-primary hover:text-primary'"
                        >
                            Grafik 2: {{ selectedPeriodLabel }} ({{ selectedStatusLabel }})
                        </button>
                        <template v-if="drillLevel === 3">
                            <span class="text-slate-300 text-xs">&rsaquo;</span>
                            <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold truncate max-w-[200px]">
                                {{ drillDownCategory || drillDownServiceName || 'Detail Layanan' }}
                            </span>
                        </template>
                    </div>

                    <h3 class="text-sm font-bold text-slate-800">
                        <template v-if="drillLevel === 1">Tren Historis & Perbandingan Status Layanan</template>
                        <template v-else-if="drillLevel === 2">Distribusi Layanan {{ selectedStatusLabel }} per OPD ({{ selectedPeriodLabel }})</template>
                        <template v-else>Daftar Layanan {{ selectedStatusLabel }} ({{ filteredDrillDownItems.length }})</template>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">
                        <template v-if="drillLevel === 1">
                            Perbandingan jumlah layanan Normal, Terkendala, dan Belum Dicek pada {{ periodTrends.length || 0 }} periode terakhir (klik batang untuk drill-down)
                        </template>
                        <template v-else-if="drillLevel === 2">
                            Klik salah satu batang OPD untuk melihat daftar layanan pada OPD tersebut
                        </template>
                        <template v-else>
                            {{ drillDownCategory ? `OPD: ${drillDownCategory}` : 'Semua OPD' }} &middot; {{ selectedPeriodLabel }}
                        </template>
                    </p>
                </div>

                <!-- LEVEL 1: Area Grafik Bar Antar-Periode -->
                <template v-if="drillLevel === 1">
                    <div v-if="periodTrends.length > 0" class="relative h-72 w-full mt-2">
                        <canvas ref="trendChartRef"></canvas>
                    </div>
                    <div v-else class="h-72 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3 text-slate-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-4 4 4 5-6" />
                            </svg>
                        </div>
                        <p class="text-sm text-slate-400 font-medium">Belum ada riwayat periode untuk dibandingkan</p>
                    </div>
                </template>

                <!-- LEVEL 2 (In-Place): Grafik 2 Distribusi OPD / Kategori -->
                <template v-else-if="drillLevel === 2">
                    <!-- Filter Status di Grafik 2 -->
                    <div class="flex flex-wrap items-center gap-1.5 mb-3">
                        <button
                            v-for="opt in [
                                { key: 'all', label: 'Semua Status' },
                                { key: 'normal', label: 'Normal' },
                                { key: 'gangguan', label: 'Terkendala' },
                                { key: 'pending', label: 'Belum Dicek' },
                            ]"
                            :key="opt.key"
                            type="button"
                            @click="drillDownStatus = opt.key"
                            class="px-2.5 py-1 rounded-lg text-[11px] font-bold border transition-colors cursor-pointer"
                            :class="drillDownStatus === opt.key
                                ? 'bg-primary text-white border-primary'
                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-primary hover:text-primary'"
                        >
                            {{ opt.label }}
                        </button>
                    </div>

                    <div v-if="opdBranchStats.length > 0" class="space-y-3">
                        <div class="relative h-52 w-full mt-1">
                            <canvas ref="opdChartRef"></canvas>
                        </div>
                        <div class="pt-2.5 border-t border-slate-100 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                    Cabang OPD ({{ opdBranchStats.length }})
                                </span>
                                <div v-if="opdTotalPages > 1" class="inline-flex items-center gap-1.5">
                                    <button
                                        type="button"
                                        @click="opdPage = Math.max(1, opdPage - 1)"
                                        :disabled="opdPage === 1"
                                        class="w-6 h-6 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold flex items-center justify-center disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                                        aria-label="Halaman sebelumnya"
                                    >
                                        &lsaquo;
                                    </button>
                                    <span class="text-[11px] font-bold text-slate-500 tabular-nums px-1">
                                        {{ opdPage }} / {{ opdTotalPages }}
                                    </span>
                                    <button
                                        type="button"
                                        @click="opdPage = Math.min(opdTotalPages, opdPage + 1)"
                                        :disabled="opdPage === opdTotalPages"
                                        class="w-6 h-6 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-xs font-bold flex items-center justify-center disabled:opacity-40 cursor-pointer disabled:cursor-not-allowed"
                                        aria-label="Halaman berikutnya"
                                    >
                                        &rsaquo;
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-1.5">
                                <button
                                    v-for="branch in paginatedOpdBranches"
                                    :key="branch.category"
                                    type="button"
                                    @click="selectOpdBranch(branch.category)"
                                    class="flex items-center justify-between gap-2 px-2.5 py-1.5 rounded-lg border border-slate-200 hover:border-primary hover:text-primary bg-slate-50/70 text-slate-700 text-[11px] font-semibold transition-all cursor-pointer text-left"
                                >
                                    <span class="truncate">{{ branch.category }}</span>
                                    <span class="px-1.5 py-0.5 rounded bg-slate-200/80 text-slate-700 text-[10px] font-extrabold shrink-0">{{ branch.total }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div v-else class="h-64 flex flex-col items-center justify-center text-center">
                        <p class="text-xs text-slate-400 font-medium">Tidak ada layanan dengan status {{ selectedStatusLabel }} pada periode ini.</p>
                    </div>
                </template>

                <!-- LEVEL 3 (In-Place): Daftar Layanan -->
                <template v-else>
                    <div class="space-y-2.5">
                        <div class="relative">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 105.65 5.65a7.5 7.5 0 0010.6 10.6z" />
                            </svg>
                            <input
                                v-model="drillDownSearch"
                                type="text"
                                placeholder="Cari nama layanan, tester, atau catatan..."
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none"
                            />
                        </div>
                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                            <div
                                v-for="item in filteredDrillDownItems"
                                :key="item.id"
                                class="p-3 rounded-xl border transition-all"
                                :class="item.status === 'gangguan'
                                    ? 'border-rose-200/80 bg-rose-50/30 hover:bg-rose-50/50'
                                    : (item.status === 'normal'
                                        ? 'border-emerald-200/80 bg-emerald-50/30 hover:bg-emerald-50/50'
                                        : 'border-slate-200 bg-slate-50/50 hover:bg-slate-50')"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <p class="text-xs font-bold text-slate-900">{{ item.service_name }}</p>
                                            <span class="px-1.5 py-0.5 rounded bg-primary text-white text-[10px] font-bold">{{ item.level }}</span>
                                            <span
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border"
                                                :class="item.status === 'gangguan'
                                                    ? 'bg-rose-100 text-rose-700 border-rose-200'
                                                    : (item.status === 'normal'
                                                        ? 'bg-emerald-100 text-emerald-700 border-emerald-200'
                                                        : 'bg-slate-200/80 text-slate-700 border-slate-300')"
                                            >
                                                {{ item.status === 'gangguan' ? 'Terkendala' : (item.status === 'normal' ? 'Normal' : 'Belum Dicek') }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            {{ item.category }} &middot; {{ item.period_date_label }} &middot; Tester: <strong class="text-slate-700">{{ item.tester_name }}</strong>
                                        </p>
                                        <p v-if="item.description && item.description !== '-'" class="text-[11px] text-slate-700 bg-white/80 border border-slate-200/70 rounded-lg px-2.5 py-1.5 mt-1.5">
                                            {{ item.description }}
                                        </p>
                                    </div>
                                    <Link
                                        :href="route('admin.periods.show', item.period_id)"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg bg-primary hover:bg-[#1d4ed8] text-white text-[11px] font-bold shrink-0 self-start"
                                    >
                                        <span>Detail</span>
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                        </svg>
                                    </Link>
                                </div>
                            </div>
                            <div v-if="filteredDrillDownItems.length === 0" class="py-8 text-center text-slate-400 text-xs">
                                Tidak ada layanan yang sesuai dengan filter.
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Ringkasan KPI Tren -->
            <div v-if="periodTrends.length > 0" class="mt-5 pt-4 border-t border-slate-100 grid grid-cols-3 gap-3 text-center">
                <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-base sm:text-lg font-black text-rose-600 leading-none">
                        {{ latestPeriod?.gangguan ?? 0 }}
                    </p>
                    <p class="text-[11px] font-medium text-slate-500 mt-1">Kendala Periode Terakhir</p>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-base sm:text-lg font-black text-emerald-600 leading-none">
                        {{ averageHealthRate }}%
                    </p>
                    <p class="text-[11px] font-medium text-slate-500 mt-1">Rata-rata Layanan Normal</p>
                </div>
                <div class="p-2.5 rounded-xl bg-slate-50/70 border border-slate-100">
                    <p class="text-base sm:text-lg font-black text-primary leading-none">
                        {{ periodTrends.length }} Periode
                    </p>
                    <p class="text-[11px] font-medium text-slate-500 mt-1">Rentang Komparasi</p>
                </div>
            </div>
        </div>

        <!-- â”€â”€â”€ Kanan: Top 5 Layanan Paling Sering Bermasalah (5 Kolom) â”€â”€â”€ -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-2 mb-2.5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100 text-[10px] font-bold uppercase tracking-wider whitespace-nowrap">
                            Prioritas Evaluasi
                        </span>
                        <div class="flex items-center gap-2 shrink-0">
                            <button
                                v-if="troubledServicesDetail.length > 0"
                                type="button"
                                @click="openDrillDown()"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition-colors cursor-pointer whitespace-nowrap"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 105.65 5.65a7.5 7.5 0 0010.6 10.6z" />
                                </svg>
                                <span>Drill-Down ({{ troubledServicesDetail.length }})</span>
                            </button>
                            <Link
                                :href="route('admin.services.index')"
                                class="text-xs font-semibold text-primary hover:underline shrink-0 whitespace-nowrap"
                            >
                                Master Layanan
                            </Link>
                        </div>
                    </div>
                    <h3 class="text-sm font-bold text-slate-800">Top 5 Layanan Paling Sering Bermasalah</h3>
                    <p class="text-xs text-slate-400 mt-0.5 leading-relaxed">Berdasarkan akumulasi riwayat kendala lintas periode</p>
                </div>

                <!-- Daftar Top 5 Layanan -->
                <div v-if="topTroubledServices.length > 0" class="space-y-3">
                    <div
                        v-for="(svc, idx) in topTroubledServices"
                        :key="svc.id"
                        @click="openDrillDown({ serviceName: svc.service_name })"
                        class="p-3.5 rounded-xl border border-slate-200/70 hover:border-rose-300 hover:bg-rose-50/20 bg-slate-50/40 transition-all cursor-pointer"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-start gap-2.5 min-w-0">
                                <!-- Nomor Peringkat -->
                                <span
                                    class="w-6 h-6 rounded-lg text-xs font-black flex items-center justify-center shrink-0 mt-0.5"
                                    :class="idx === 0
                                        ? 'bg-rose-600 text-white shadow-xs'
                                        : idx === 1
                                            ? 'bg-rose-100 text-rose-700 border border-rose-200'
                                            : 'bg-slate-200/80 text-slate-700'"
                                >
                                    #{{ idx + 1 }}
                                </span>

                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 truncate" :title="svc.service_name">
                                        {{ svc.service_name }}
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                                        <span class="px-2 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-semibold text-slate-600 truncate max-w-[180px]">
                                            {{ svc.category }}
                                        </span>
                                        <span class="px-2 py-0.5 rounded-md bg-primary text-white text-[10px] font-bold">
                                            {{ svc.level }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Badge Jumlah Kendala -->
                            <div class="text-right shrink-0">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-extrabold">
                                    {{ svc.total_issues }}x Kendala
                                </span>
                                <p class="text-[10px] font-semibold mt-1" :class="svc.active_issues > 0 ? 'text-rose-600' : 'text-emerald-600'">
                                    {{ svc.active_issues > 0 ? `${svc.active_issues} masih aktif` : 'Sudah teratasi' }}
                                </p>
                            </div>
                        </div>

                        <!-- Catatan Kendala Terakhir -->
                        <div v-if="svc.last_description || svc.last_period_label" class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between gap-2 text-[11px] text-slate-500">
                            <span class="truncate italic">
                                {{ svc.last_description ? `"${svc.last_description}"` : 'Tercatat dalam histori kendala' }}
                            </span>
                            <span v-if="svc.last_period_label" class="text-[10px] text-slate-400 font-semibold shrink-0">
                                {{ svc.last_period_label }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-else class="h-64 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-emerald-50 border border-emerald-100 flex items-center justify-center mb-3 text-emerald-500">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-slate-700">Belum Ada Riwayat Layanan Bermasalah</p>
                    <p class="text-xs text-slate-400 mt-1 max-w-xs">
                        Seluruh layanan yang diuji berada dalam kondisi normal atau belum ada laporan gangguan.
                    </p>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-400">
                <span>Klik layanan atau batang grafik untuk drill-down detail kendala</span>
            </div>
        </div>
    </div>

</template>
