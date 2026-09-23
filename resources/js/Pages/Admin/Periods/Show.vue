<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    period:       { type: Object, required: true },
    distribution: { type: Array,  default: () => [] },
});

const page = usePage();
const statusFilter = ref('all');
const searchQuery = ref('');

// ✨ Toast Notification ✨
const toast = ref('');
const toastType = ref('success');
let toastTimer = null;

function showToast(msg, type = 'success') {
    if (toastTimer) clearTimeout(toastTimer);
    toast.value = msg;
    toastType.value = type;
    toastTimer = setTimeout(() => { toast.value = ''; }, 4000);
}

// Watch flash from controller redirect
watch(() => page.props.flash, (newFlash) => {
    if (newFlash?.success) {
        showToast(newFlash.success, 'success');
    } else if (newFlash?.error) {
        showToast(newFlash.error, 'error');
    }
}, { deep: true });

onMounted(() => {
    if (page.props.flash?.success) {
        showToast(page.props.flash.success, 'success');
    } else if (page.props.flash?.error) {
        showToast(page.props.flash.error, 'error');
    }
});

// ─── Status Toggle (Selesaikan / Buka Kembali) ───────────────────
const isUpdatingStatus = ref(false);

function togglePeriodStatus() {
    const nextStatus = props.period.status === 'proses' ? 'selesai' : 'proses';
    isUpdatingStatus.value = true;
    router.put(route('admin.periods.update', props.period.id), {
        status: nextStatus,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            const err = errors.status || Object.values(errors)[0] || 'Gagal mengubah status periode.';
            showToast(err, 'error');
        },
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

// ─── Tester Workload Helpers ─────────────────────────────────────
function getTesterProgress(group) {
    if (!group.total_services || group.total_services === 0) return 0;
    return Math.round((group.checked_services / group.total_services) * 100);
}

function getTesterStatusLabel(group) {
    const pct = getTesterProgress(group);
    if (pct === 100) return 'Selesai';
    if (pct > 0) return 'Proses';
    return 'Menunggu';
}

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
        <!-- ✨ Toast Flash Notification ✨ -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
            enter-to-class="transform translate-y-0 opacity-100 sm:translate-x-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 sm:translate-x-0"
            leave-to-class="transform translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
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
                <div class="flex items-center gap-3">
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
                >
                    <svg class="w-4 h-4 stroke-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- ── Top Header Card (Clean & Minimal Redesign) ──────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 sm:p-7 shadow-xs mb-6">
            <!-- Back Navigation -->
            <div class="mb-4">
                <Link
                    :href="route('admin.periods.index')"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-blue-600 transition-colors group cursor-pointer"
                >
                    <span class="text-sm transition-transform group-hover:-translate-x-0.5">&larr;</span>
                    <span>Kembali</span>
                </Link>
            </div>

            <!-- Title & Action Toolbar -->
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-5">
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900 tracking-tight">
                            Detail Periode: {{ period.title }}
                        </h2>
                        <!-- Soft Blue Badge for AKTIF / Slate for NONAKTIF -->
                        <span
                            v-if="period.status === 'proses'"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase bg-blue-50 border border-blue-200/60 text-blue-600"
                        >
                            AKTIF
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wide uppercase bg-slate-100 border border-slate-200 text-slate-500"
                        >
                            NONAKTIF
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 mt-2 font-normal">
                        Dibuat pada {{ period.created_at_label }} oleh {{ period.creator_name }}
                        <span v-if="period.deadline_date">
                            &bull; <span class="text-amber-600 font-semibold">Tenggat Waktu: {{ period.deadline_date }}</span>
                        </span>
                    </p>
                </div>

                <!-- Export & Action Buttons -->
                <div class="flex items-center gap-2.5 flex-wrap">
                    <!-- Edit Periode (Outlined Minimal) -->
                    <Link
                        :href="route('admin.periods.edit', period.id)"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all duration-150 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/>
                        </svg>
                        <span>Edit Periode</span>
                    </Link>

                    <!-- Export Excel (Outlined Minimal) -->
                    <a
                        :href="route('admin.periods.export.excel', period.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all duration-150 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        <span>Export Excel</span>
                    </a>

                    <!-- Export PDF (Outlined Minimal) -->
                    <a
                        :href="route('admin.periods.export.pdf', period.id)"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-2xs transition-all duration-150 cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Export PDF</span>
                    </a>

                    <!-- Toggle Status (Primary Solid Blue Button) -->
                    <button
                        type="button"
                        @click="togglePeriodStatus"
                        :disabled="isUpdatingStatus"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-sm shadow-blue-600/20 transition-all duration-150 cursor-pointer disabled:opacity-60"
                    >
                        <svg v-if="period.status === 'proses'" class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <svg v-else class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span>{{ isUpdatingStatus ? 'Memproses...' : (period.status === 'proses' ? 'Selesaikan Periode' : 'Buka Kembali Periode') }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Infographic Executive KPI Cards ──────────────────── -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- TOTAL LAYANAN -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">TOTAL LAYANAN</p>
                    <div class="w-7 h-7 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-slate-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <rect x="2" y="3" width="20" height="14" rx="2"/><path stroke-linecap="round" d="M8 21h8M12 17v4"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ period.total_services }}</p>
                    <p class="text-xs text-slate-400 mt-1 font-normal">Layanan Digital Jatim</p>
                </div>
            </div>

            <!-- LAYANAN NORMAL -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">LAYANAN NORMAL</p>
                    <div class="w-7 h-7 rounded-xl bg-blue-50/80 border border-blue-100 text-blue-500 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ period.normal_services }}</p>
                    <p class="text-xs text-blue-600 font-medium mt-1 flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <span>Berjalan Optimal</span>
                    </p>
                </div>
            </div>

            <!-- TERKENDALA -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">TERKENDALA</p>
                    <div class="w-7 h-7 rounded-xl bg-slate-50 border border-slate-100 text-slate-400 flex items-center justify-center">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                </div>
                <div>
                    <p class="text-3xl font-extrabold text-slate-900 mt-2.5">{{ period.issues_services }}</p>
                    <p class="text-xs text-slate-400 mt-1 font-normal">Perlu Penanganan</p>
                </div>
            </div>

            <!-- PROGRESS CEK -->
            <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-xs flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">PROGRESS CEK</p>
                    <span class="text-xs font-semibold text-slate-500 bg-slate-100/90 px-2 py-0.5 rounded-lg">
                        {{ period.checked_services }}/{{ period.total_services }}
                    </span>
                </div>
                <div class="mt-2.5">
                    <div class="flex items-baseline justify-between">
                        <p class="text-3xl font-extrabold text-slate-900">{{ period.progress_percent }}%</p>
                        <span class="text-xs text-slate-400 font-normal">dari total layanan</span>
                    </div>
                    <div class="w-full h-1.5 bg-slate-100 rounded-full mt-2.5 overflow-hidden">
                        <div
                            class="h-full rounded-full bg-blue-600 transition-all duration-500"
                            :style="{ width: `${period.progress_percent}%` }"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Distribusi & Beban Kerja Tester ─────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-xs mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider">
                        DISTRIBUSI & BEBAN KERJA TESTER
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5 font-normal">
                        Penugasan pengecekan performa dan status layanan
                    </p>
                </div>
                <span class="text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1 rounded-xl">
                    {{ distribution.length }} Personel
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mt-5">
                <div
                    v-for="group in distribution"
                    :key="group.tester_id"
                    class="p-4.5 rounded-2xl border border-slate-100 bg-white hover:border-slate-200 transition-all shadow-2xs flex flex-col justify-between"
                >
                    <!-- Row 1: Avatar, Info & Fraction -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div
                                :class="[
                                    'w-9 h-9 rounded-full flex items-center justify-center font-bold text-xs shrink-0',
                                    getTesterProgress(group) > 0 ? 'bg-blue-600 text-white' : 'bg-slate-200 text-slate-600'
                                ]"
                            >
                                {{ group.initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-800 leading-tight truncate">{{ group.name }}</p>
                                <p class="text-xs text-slate-400 font-normal truncate mt-0.5">{{ group.email }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span
                                :class="[
                                    'inline-block text-xs font-bold px-2 py-0.5 rounded-lg',
                                    getTesterProgress(group) > 0
                                        ? 'bg-blue-50 text-blue-600 border border-blue-100/60'
                                        : 'bg-slate-100 text-slate-600'
                                ]"
                            >
                                {{ group.checked_services }}/{{ group.total_services }}
                            </span>
                            <span class="text-[10.5px] text-slate-400 font-normal block mt-0.5">layanan selesai</span>
                        </div>
                    </div>

                    <!-- Row 2: Progress Bar -->
                    <div class="w-full h-1 bg-slate-100 rounded-full mt-3.5 overflow-hidden">
                        <div
                            class="h-full bg-blue-600 rounded-full transition-all duration-300"
                            :style="{ width: `${getTesterProgress(group)}%` }"
                        ></div>
                    </div>

                    <!-- Row 3: Status Text & Percentage -->
                    <div class="flex items-center justify-between text-xs mt-2">
                        <span class="text-[11px] text-slate-400 font-medium">
                            {{ getTesterStatusLabel(group) }}
                        </span>
                        <span
                            :class="[
                                'text-xs font-bold',
                                getTesterProgress(group) > 0 ? 'text-blue-600' : 'text-slate-400'
                            ]"
                        >
                            {{ getTesterProgress(group) }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── Detailed Service Checklist Table ─────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs overflow-hidden">
            <!-- Table Toolbar -->
            <div class="px-6 py-4.5 border-b border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <span class="text-sm font-bold text-slate-800">Daftar Layanan</span>
                    <span class="text-xs font-medium text-slate-400">({{ filteredServices.length }} dari {{ allServices.length }})</span>
                </div>

                <!-- Filter Tabs & Search -->
                <div class="flex items-center gap-2 flex-wrap">
                    <!-- Status Filter Tabs -->
                    <div class="inline-flex p-1 bg-slate-100/80 rounded-xl text-xs font-semibold text-slate-600">
                        <button
                            type="button"
                            @click="statusFilter = 'all'"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-all cursor-pointer',
                                statusFilter === 'all'
                                    ? 'bg-white text-blue-600 shadow-2xs font-bold'
                                    : 'text-slate-500 hover:text-slate-800'
                            ]"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'normal'"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-all cursor-pointer',
                                statusFilter === 'normal'
                                    ? 'bg-white text-emerald-700 shadow-2xs font-bold'
                                    : 'text-slate-500 hover:text-slate-800'
                            ]"
                        >
                            Normal
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'gangguan'"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-all cursor-pointer',
                                statusFilter === 'gangguan'
                                    ? 'bg-white text-rose-700 shadow-2xs font-bold'
                                    : 'text-slate-500 hover:text-slate-800'
                            ]"
                        >
                            Kendala
                        </button>
                        <button
                            type="button"
                            @click="statusFilter = 'pending'"
                            :class="[
                                'px-2.5 py-1 rounded-lg transition-all cursor-pointer',
                                statusFilter === 'pending'
                                    ? 'bg-white text-slate-700 shadow-2xs font-bold'
                                    : 'text-slate-500 hover:text-slate-800'
                            ]"
                        >
                            Belum Dicek
                        </button>
                    </div>

                    <!-- Search Input -->
                    <div class="relative">
                        <input
                            v-model="searchQuery"
                            type="text"
                            placeholder="Cari layanan / tester..."
                            class="pl-8 pr-3.5 py-1.5 text-xs bg-slate-50/70 border border-slate-200 rounded-xl text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:bg-white w-48 sm:w-56 transition-all"
                        />
                        <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
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
                            class="hover:bg-slate-50/60 transition-colors"
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
                                            'w-6 h-6 rounded-full flex items-center justify-center text-white text-[10px] font-bold shrink-0',
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
