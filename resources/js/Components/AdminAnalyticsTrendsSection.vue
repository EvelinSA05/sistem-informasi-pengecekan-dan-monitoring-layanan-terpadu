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
});

const showDrillDownModal = ref(false);
const drillDownStep = ref('opd'); // 'opd' (Grafik 2: Cabang OPD) | 'services' (Level 3: Detail Layanan)
const drillDownSearch = ref('');
const drillDownPeriodId = ref('all');
const drillDownCategory = ref('');
const drillDownServiceName = ref('');

const opdChartRef = ref(null);
let opdChartInstance = null;

function openDrillDown(options = {}) {
    drillDownPeriodId.value = options.periodId ? String(options.periodId) : 'all';
    drillDownCategory.value = options.category || '';
    drillDownServiceName.value = options.serviceName || '';
    drillDownSearch.value = options.serviceName || '';
    drillDownStep.value = options.serviceName ? 'services' : (options.step || 'opd');
    showDrillDownModal.value = true;
}

function goToStep(step) {
    if (step === 'all') {
        drillDownPeriodId.value = 'all';
        drillDownCategory.value = '';
        drillDownServiceName.value = '';
        drillDownSearch.value = '';
        drillDownStep.value = 'opd';
        return;
    }
    if (step === 'opd') {
        drillDownCategory.value = '';
        drillDownServiceName.value = '';
        drillDownSearch.value = '';
        drillDownStep.value = 'opd';
    }
}

function selectOpdBranch(category) {
    drillDownCategory.value = category;
    drillDownServiceName.value = '';
    drillDownSearch.value = '';
    drillDownStep.value = 'services';
}

const selectedPeriodLabel = computed(() => {
    if (drillDownPeriodId.value === 'all') return 'Semua Periode';
    const found = (props.periodTrends || []).find(p => String(p.id) === drillDownPeriodId.value);
    return found?.date_label || 'Periode Terpilih';
});

const periodScopedItems = computed(() => {
    const items = props.troubledServicesDetail || [];
    if (drillDownPeriodId.value === 'all') return items;
    return items.filter(item => String(item.period_id) === drillDownPeriodId.value);
});

const opdBranchStats = computed(() => {
    const counts = new Map();
    periodScopedItems.value.forEach(item => {
        const cat = item.category || 'Lainnya';
        const existing = counts.get(cat) || { category: cat, total: 0, active: 0 };
        existing.total += 1;
        if (item.handling_status !== 'selesai') {
            existing.active += 1;
        }
        counts.set(cat, existing);
    });
    return Array.from(counts.values()).sort((a, b) => b.total - a.total);
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
                    const p = props.periodTrends?.[idx];
                    if (p) {
                        openDrillDown({ periodId: p.id });
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
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 11 },
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        afterBody: (items) => {
                            const idx = items?.[0]?.dataIndex;
                            const p = props.periodTrends?.[idx];
                            if (!p) return '';
                            return `Tingkat Normal: ${p.health_rate ?? 0}% (${p.checked}/${p.total} dicek)`;
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
    if (!showDrillDownModal.value || drillDownStep.value !== 'opd' || !opdChartRef.value) return;

    const topBranches = opdBranchStats.value.slice(0, 10);
    if (!topBranches.length) return;

    const labels = topBranches.map(b => b.category.length > 28 ? `${b.category.slice(0, 26)}…` : b.category);
    const activeData = topBranches.map(b => b.active);
    const resolvedData = topBranches.map(b => Math.max(0, b.total - b.active));

    opdChartInstance = new Chart(opdChartRef.value, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Belum Selesai',
                    data: activeData,
                    backgroundColor: '#e11d48',
                    borderRadius: 6,
                    barPercentage: 0.72,
                },
                {
                    label: 'Sudah Selesai',
                    data: resolvedData,
                    backgroundColor: '#10b981',
                    borderRadius: 6,
                    barPercentage: 0.72,
                },
            ],
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
                    padding: 10,
                    cornerRadius: 10,
                    callbacks: {
                        title: (items) => {
                            const idx = items?.[0]?.dataIndex;
                            return topBranches[idx]?.category || '';
                        },
                        afterBody: () => 'Klik batang grafik untuk lihat daftar layanan →',
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
    [showDrillDownModal, drillDownStep, drillDownPeriodId],
    async ([isOpen, step]) => {
        if (isOpen && step === 'opd') {
            await nextTick();
            buildOpdChart();
        } else if (opdChartInstance) {
            opdChartInstance.destroy();
            opdChartInstance = null;
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- ─── Kiri: Grafik Tren & Perbandingan Antar-Periode (7 Kolom) ─── -->
        <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-primary border border-blue-100 text-[10px] font-bold uppercase tracking-wider mb-1.5">
                            Analytics Antar-Periode
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Tren Historis & Perbandingan Status Layanan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Perbandingan jumlah layanan Normal vs Terkendala pada {{ periodTrends.length || 0 }} periode terakhir</p>
                    </div>

                    <!-- Badge Perbandingan Periode Terakhir vs Sebelumnya -->
                    <div v-if="latestPeriod && previousPeriod" class="shrink-0">
                        <span
                            v-if="latestPeriod.issue_delta < 0"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold"
                        >
                            ↓ {{ Math.abs(latestPeriod.issue_delta) }} kendala vs periode lalu
                        </span>
                        <span
                            v-else-if="latestPeriod.issue_delta > 0"
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold"
                        >
                            ↑ +{{ latestPeriod.issue_delta }} kendala vs periode lalu
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold"
                        >
                            = Stabil vs periode lalu
                        </span>
                    </div>
                </div>

                <!-- Area Grafik Bar Antar-Periode -->
                <div v-if="periodTrends.length > 0" class="relative h-64 w-full mt-2">
                    <canvas ref="trendChartRef"></canvas>
                </div>
                <div v-else class="h-64 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-xl bg-slate-50 flex items-center justify-center mb-3 text-slate-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3v18h18M7 16l4-4 4 4 5-6" />
                        </svg>
                    </div>
                    <p class="text-sm text-slate-400 font-medium">Belum ada riwayat periode untuk dibandingkan</p>
                </div>
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

        <!-- ─── Kanan: Top 5 Layanan Paling Sering Bermasalah (5 Kolom) ─── -->
        <div class="lg:col-span-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between gap-2 mb-4">
                    <div>
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-600 border border-rose-100 text-[10px] font-bold uppercase tracking-wider mb-1.5">
                            Prioritas Evaluasi
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">Top 5 Layanan Paling Sering Bermasalah</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Berdasarkan akumulasi riwayat kendala lintas periode</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button
                            v-if="troubledServicesDetail.length > 0"
                            type="button"
                            @click="openDrillDown()"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold transition-colors cursor-pointer"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 105.65 5.65a7.5 7.5 0 0010.6 10.6z" />
                            </svg>
                            <span>Drill-Down ({{ troubledServicesDetail.length }})</span>
                        </button>
                        <Link
                            :href="route('admin.services.index')"
                            class="text-xs font-semibold text-primary hover:underline shrink-0"
                        >
                            Master Layanan
                        </Link>
                    </div>
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

    <!-- Modal Drill-Down Layanan Bermasalah -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showDrillDownModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm" @click="showDrillDownModal = false"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-3xl max-h-[85vh] flex flex-col overflow-hidden z-10 border border-slate-200">
                    <!-- Modal Header + Breadcrumb Cabang Drill-Down -->
                    <div class="px-6 py-4 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/70">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] font-bold mb-1.5">
                                <button
                                    type="button"
                                    @click="goToStep('all')"
                                    class="px-2 py-0.5 rounded-full border transition-colors cursor-pointer"
                                    :class="drillDownPeriodId === 'all' && drillDownStep === 'opd'
                                        ? 'bg-primary text-white border-primary'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-primary hover:text-primary'"
                                >
                                    Level 1: Semua Periode
                                </button>
                                <span v-if="drillDownPeriodId !== 'all'" class="text-slate-400">›</span>
                                <button
                                    v-if="drillDownPeriodId !== 'all'"
                                    type="button"
                                    @click="goToStep('opd')"
                                    class="px-2 py-0.5 rounded-full border transition-colors cursor-pointer"
                                    :class="drillDownStep === 'opd'
                                        ? 'bg-primary text-white border-primary'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-primary hover:text-primary'"
                                >
                                    Level 2: {{ selectedPeriodLabel }}
                                </button>
                                <span v-if="drillDownStep === 'services'" class="text-slate-400">›</span>
                                <span
                                    v-if="drillDownStep === 'services'"
                                    class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 truncate max-w-[220px]"
                                >
                                    Level 3: {{ drillDownCategory || drillDownServiceName || 'Daftar Layanan' }}
                                </span>
                            </div>
                            <h3 class="text-base font-extrabold text-slate-900">
                                <template v-if="drillDownStep === 'opd'">
                                    Distribusi Kendala per OPD / Kategori ({{ selectedPeriodLabel }})
                                </template>
                                <template v-else>
                                    Daftar Layanan Bermasalah ({{ filteredDrillDownItems.length }})
                                </template>
                            </h3>
                        </div>
                        <button
                            type="button"
                            @click="showDrillDownModal = false"
                            class="p-2 rounded-xl text-slate-400 hover:text-slate-700 hover:bg-slate-200/60 transition-colors cursor-pointer shrink-0"
                            aria-label="Tutup modal"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Filter Bar -->
                    <div class="px-6 py-3 border-b border-slate-100 flex flex-col sm:flex-row gap-2.5 bg-white">
                        <div v-if="drillDownStep === 'services'" class="relative flex-1">
                            <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 105.65 5.65a7.5 7.5 0 0010.6 10.6z" />
                            </svg>
                            <input
                                v-model="drillDownSearch"
                                type="text"
                                placeholder="Cari nama layanan, OPD, tester, atau catatan..."
                                class="w-full pl-9 pr-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary focus:ring-2 focus:ring-primary/15 outline-none"
                            />
                        </div>
                        <div v-else class="flex-1 flex items-center justify-between gap-2 text-xs text-slate-500">
                            <span>Klik batang grafik OPD di bawah untuk menuju ke detail layanan.</span>
                            <button
                                type="button"
                                @click="drillDownStep = 'services'"
                                class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[11px] shrink-0 cursor-pointer"
                            >
                                Lihat Semua ({{ periodScopedItems.length }})
                            </button>
                        </div>
                        <select
                            v-model="drillDownPeriodId"
                            class="px-3 py-2 text-xs bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-primary outline-none text-slate-700 sm:w-56"
                        >
                            <option value="all">Semua Periode</option>
                            <option v-for="p in periodTrends" :key="p.id" :value="String(p.id)">
                                {{ p.date_label }} ({{ p.gangguan }} kendala)
                            </option>
                        </select>
                    </div>

                    <!-- LEVEL 2: Grafik 2 Distribusi OPD / Kategori -->
                    <div v-if="drillDownStep === 'opd'" class="flex-1 overflow-y-auto p-6 space-y-4">
                        <div v-if="opdBranchStats.length > 0" class="bg-slate-50/60 border border-slate-200/70 rounded-2xl p-4">
                            <div class="relative h-64 w-full">
                                <canvas ref="opdChartRef"></canvas>
                            </div>
                        </div>

                        <!-- Daftar OPD yang Bisa Diklik -->
                        <div v-if="opdBranchStats.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button
                                v-for="branch in opdBranchStats"
                                :key="branch.category"
                                type="button"
                                @click="selectOpdBranch(branch.category)"
                                class="p-3 rounded-xl border border-slate-200 hover:border-primary hover:bg-blue-50/30 bg-white text-left flex items-center justify-between gap-3 transition-all cursor-pointer group"
                            >
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 group-hover:text-primary truncate">{{ branch.category }}</p>
                                    <p class="text-[11px] text-slate-500 mt-0.5">{{ branch.active }} aktif · {{ branch.total - branch.active }} selesai</p>
                                </div>
                                <span class="px-2.5 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[11px] font-extrabold shrink-0">
                                    {{ branch.total }} →
                                </span>
                            </button>
                        </div>

                        <div v-else class="py-12 text-center text-slate-400 text-xs">
                            Tidak ada layanan bermasalah pada periode ini.
                        </div>
                    </div>

                    <!-- LEVEL 3: Daftar Layanan Bermasalah -->
                    <div v-else class="flex-1 overflow-y-auto p-6 space-y-3">
                        <div
                            v-for="item in filteredDrillDownItems"
                            :key="item.id"
                            class="p-4 rounded-2xl border border-rose-200/80 bg-rose-50/30 hover:bg-rose-50/50 transition-all"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-sm font-bold text-slate-900">{{ item.service_name }}</p>
                                        <span class="px-2 py-0.5 rounded-md bg-primary text-white text-[10px] font-bold">{{ item.level }}</span>
                                        <span
                                            class="px-2 py-0.5 rounded-md text-[10px] font-bold uppercase border"
                                            :class="item.handling_status === 'perlu_pengecekan_ulang'
                                                ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                : (item.handling_status === 'selesai'
                                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                    : 'bg-rose-100 text-rose-700 border-rose-200')"
                                        >
                                            {{ item.handling_status === 'perlu_pengecekan_ulang' ? 'Perlu Cek Ulang' : (item.handling_status === 'selesai' ? 'Selesai' : 'Belum Tindak Lanjut') }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-1">
                                        {{ item.category }} · Periode: <strong class="text-slate-700">{{ item.period_date_label }}</strong> · Tester: <strong class="text-slate-700">{{ item.tester_name }}</strong>
                                    </p>
                                    <p class="text-xs text-rose-800 bg-white/80 border border-rose-100 rounded-xl px-3 py-2 mt-2">
                                        <span class="font-semibold">Catatan:</span> {{ item.description }}
                                    </p>
                                </div>
                                <Link
                                    :href="route('admin.periods.show', item.period_id)"
                                    @click="showDrillDownModal = false"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-primary hover:bg-[#1d4ed8] text-white text-xs font-bold shadow-xs transition-all shrink-0 self-start"
                                >
                                    <span>Detail Periode</span>
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <div v-if="filteredDrillDownItems.length === 0" class="py-12 text-center text-slate-400 text-xs">
                            Tidak ada layanan bermasalah yang sesuai dengan filter.
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/70 flex justify-end">
                        <button
                            type="button"
                            @click="showDrillDownModal = false"
                            class="px-5 py-2 rounded-xl bg-white border border-slate-200 hover:bg-slate-100 text-xs font-semibold text-slate-700 cursor-pointer"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

