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
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    align: 'end',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        pointStyle: 'circle',
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

onUnmounted(() => {
    if (trendChartInstance) {
        trendChartInstance.destroy();
        trendChartInstance = null;
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
                    <Link
                        :href="route('admin.services.index')"
                        class="text-xs font-semibold text-primary hover:underline shrink-0"
                    >
                        Master Layanan
                    </Link>
                </div>

                <!-- Daftar Top 5 Layanan -->
                <div v-if="topTroubledServices.length > 0" class="space-y-3">
                    <div
                        v-for="(svc, idx) in topTroubledServices"
                        :key="svc.id"
                        class="p-3.5 rounded-xl border border-slate-200/70 hover:border-slate-300 bg-slate-50/40 transition-all"
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
                <span>Menampilkan maksimal 5 layanan dengan frekuensi gangguan tertinggi</span>
            </div>
        </div>
    </div>
</template>

