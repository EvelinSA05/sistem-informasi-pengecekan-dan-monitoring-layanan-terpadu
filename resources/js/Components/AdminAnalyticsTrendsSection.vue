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

const drillLevel = ref(1); // 1 = Grafik 1 (Tren Periode) | 2 = Grafik 2 (Distribusi OPD) | 3 = Detail Layanan
const drillDownSearch = ref('');
const drillDownPeriodId = ref('all');
const drillDownCategory = ref('');
const drillDownServiceName = ref('');
const analyticsCardRef = ref(null);

const opdChartRef = ref(null);
let opdChartInstance = null;

async function openDrillDown(options = {}) {
    drillDownPeriodId.value = options.periodId ? String(options.periodId) : 'all';
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
    if (drillLevel.value !== 2 || !opdChartRef.value) return;

    const topBranches = opdBranchStats.value.slice(0, 10);
    if (!topBranches.length) return;

    const labels = topBranches.map(b => b.category.length > 28 ? `${b.category.slice(0, 26)}â€¦` : b.category);
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
                        afterBody: () => 'Klik batang grafik untuk lihat daftar layanan â†’',
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
    [drillLevel, drillDownPeriodId],
    async ([level]) => {
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">
        <!-- â”€â”€â”€ Kiri: Grafik Tren & Cabang Drill-Down In-Place (7 Kolom) â”€â”€â”€ -->
        <div ref="analyticsCardRef" class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
            <div>
                <!-- Header Card + Breadcrumb Drill-Down In-Place -->
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2 mb-4">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5 mb-1.5">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-blue-50 text-primary border border-blue-100 text-[10px] font-bold uppercase tracking-wider">
                                Analytics Antar-Periode
                            </span>
                            <template v-if="drillLevel > 1">
                                <span class="text-slate-300 text-xs">â€º</span>
                                <button
                                    type="button"
                                    @click="goToStep(1)"
                                    class="px-2 py-0.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 text-[10px] font-bold cursor-pointer transition-colors"
                                >
                                    Grafik 1
                                </button>
                                <span class="text-slate-300 text-xs">â€º</span>
                                <button
                                    type="button"
                                    @click="goToStep(2)"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold border transition-colors cursor-pointer"
                                    :class="drillLevel === 2
                                        ? 'bg-primary text-white border-primary'
                                        : 'bg-white text-slate-600 border-slate-200 hover:border-primary hover:text-primary'"
                                >
                                    Grafik 2: {{ selectedPeriodLabel }}
                                </button>
                                <template v-if="drillLevel === 3">
                                    <span class="text-slate-300 text-xs">â€º</span>
                                    <span class="px-2 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-[10px] font-bold truncate max-w-[170px]">
                                        {{ drillDownCategory || drillDownServiceName || 'Detail Layanan' }}
                                    </span>
                                </template>
                            </template>
                        </div>
                        <h3 class="text-sm font-bold text-slate-800">
                            <template v-if="drillLevel === 1">Tren Historis & Perbandingan Status Layanan</template>
                            <template v-else-if="drillLevel === 2">Distribusi Kendala per OPD / Kategori ({{ selectedPeriodLabel }})</template>
                            <template v-else>Daftar Layanan Bermasalah ({{ filteredDrillDownItems.length }})</template>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <template v-if="drillLevel === 1">
                                Perbandingan jumlah layanan Normal vs Terkendala pada {{ periodTrends.length || 0 }} periode terakhir (klik batang untuk drill-down)
                            </template>
                            <template v-else-if="drillLevel === 2">
                                Klik salah satu batang OPD untuk melihat layanan bermasalah pada periode ini
                            </template>
                            <template v-else>
                                {{ drillDownCategory ? `OPD: ${drillDownCategory}` : 'Semua OPD' }} Â· {{ selectedPeriodLabel }}
                            </template>
                        </p>
                    </div>

                    <!-- Kanan Header: Badge Delta (Level 1) atau Tombol Kembali (Level 2 & 3) -->
                    <div class="shrink-0 flex items-center gap-2">
                        <template v-if="drillLevel === 1 && latestPeriod && previousPeriod">
                            <span
                                v-if="latestPeriod.issue_delta < 0"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-bold"
                            >
                                â†“ {{ Math.abs(latestPeriod.issue_delta) }} kendala vs periode lalu
                            </span>
                            <span
                                v-else-if="latestPeriod.issue_delta > 0"
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 text-xs font-bold"
                            >
                                â†‘ +{{ latestPeriod.issue_delta }} kendala vs periode lalu
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-slate-100 text-slate-600 border border-slate-200 text-xs font-bold"
                            >
                                = Stabil vs periode lalu
                            </span>
                        </template>
                        <template v-else-if="drillLevel > 1">
                            <button
                                type="button"
                                @click="goToStep(drillLevel === 3 ? 2 : 1)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition-colors cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                                </svg>
                                <span>{{ drillLevel === 3 ? 'Kembali ke Grafik 2' : 'Kembali ke Grafik 1' }}</span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- LEVEL 1: Area Grafik Bar Antar-Periode -->
                <template v-if="drillLevel === 1">
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
                </template>

                <!-- LEVEL 2 (In-Place): Grafik 2 Distribusi OPD / Kategori -->
                <template v-else-if="drillLevel === 2">
                    <div v-if="opdBranchStats.length > 0" class="space-y-3">
                        <div class="relative h-56 w-full mt-1">
                            <canvas ref="opdChartRef"></canvas>
                        </div>
                        <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mr-1">Cabang OPD:</span>
                            <button
                                v-for="branch in opdBranchStats"
                                :key="branch.category"
                                type="button"
                                @click="selectOpdBranch(branch.category)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 hover:border-primary hover:text-primary bg-slate-50/70 text-slate-700 text-[11px] font-semibold transition-all cursor-pointer"
                            >
                                <span class="truncate max-w-[180px]">{{ branch.category }}</span>
                                <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 text-[10px] font-extrabold">{{ branch.total }}</span>
                            </button>
                        </div>
                    </div>
                    <div v-else class="h-64 flex flex-col items-center justify-center text-center">
                        <p class="text-xs text-slate-400 font-medium">Tidak ada layanan bermasalah pada periode ini.</p>
                    </div>
                </template>

                <!-- LEVEL 3 (In-Place): Daftar Layanan Bermasalah -->
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
                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <div
                                v-for="item in filteredDrillDownItems"
                                :key="item.id"
                                class="p-3 rounded-xl border border-rose-200/80 bg-rose-50/30 hover:bg-rose-50/50 transition-all"
                            >
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-2.5">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <p class="text-xs font-bold text-slate-900">{{ item.service_name }}</p>
                                            <span class="px-1.5 py-0.5 rounded bg-primary text-white text-[10px] font-bold">{{ item.level }}</span>
                                            <span
                                                class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase border"
                                                :class="item.handling_status === 'perlu_pengecekan_ulang'
                                                    ? 'bg-amber-50 text-amber-700 border-amber-200'
                                                    : (item.handling_status === 'selesai'
                                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                                        : 'bg-rose-100 text-rose-700 border-rose-200')"
                                            >
                                                {{ item.handling_status === 'perlu_pengecekan_ulang' ? 'Perlu Cek Ulang' : (item.handling_status === 'selesai' ? 'Selesai' : 'Belum Tindak Lanjut') }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-slate-500 mt-0.5">
                                            {{ item.category }} Â· {{ item.period_date_label }} Â· Tester: <strong class="text-slate-700">{{ item.tester_name }}</strong>
                                        </p>
                                        <p class="text-[11px] text-rose-800 bg-white/80 border border-rose-100 rounded-lg px-2.5 py-1.5 mt-1.5">
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
                                Tidak ada layanan bermasalah yang sesuai dengan filter.
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

</template>
