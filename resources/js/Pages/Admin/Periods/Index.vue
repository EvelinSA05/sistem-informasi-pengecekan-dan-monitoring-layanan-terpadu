<script setup>
import { ref, watch, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    periods: { type: Array, default: () => [] },
    flash:   { type: Object, default: () => ({}) },
});

// ─── Flash Notification ──────────────────────────────────────────
const flashMsg = ref(props.flash?.success || props.flash?.error || '');
const flashType = ref(props.flash?.error ? 'error' : 'success');
let flashTimer = null;
const progressWidth = ref(100);
const flashDuration = ref(4000);

function clearFlashTimer() {
    if (flashTimer) {
        clearTimeout(flashTimer);
        flashTimer = null;
    }
}

function closeFlash() {
    clearFlashTimer();
    flashMsg.value = '';
}

function showFlash(msg, type = 'success', duration = 4000) {
    clearFlashTimer();
    flashMsg.value = msg;
    flashType.value = type;
    flashDuration.value = duration;
    
    progressWidth.value = 100;
    setTimeout(() => {
        if (flashMsg.value === msg) {
            progressWidth.value = 0;
        }
    }, 50);

    flashTimer = setTimeout(() => {
        flashMsg.value = '';
        flashTimer = null;
    }, duration);
}

// Watch props.flash if updated via Inertia visit / redirect
watch(() => props.flash, (newFlash) => {
    if (newFlash?.success) {
        showFlash(newFlash.success, 'success', 4000);
    } else if (newFlash?.error) {
        showFlash(newFlash.error, 'error', 4000);
    }
}, { deep: true });

onMounted(() => {
    if (flashMsg.value) {
        flashDuration.value = 4000;
        progressWidth.value = 100;
        setTimeout(() => {
            progressWidth.value = 0;
        }, 50);

        flashTimer = setTimeout(() => {
            flashMsg.value = '';
            flashTimer = null;
        }, 4000);
    }
});

onUnmounted(() => {
    clearFlashTimer();
});

// ─── Avatar color helper ─────────────────────────────────────────
const testerColorPalette = {
    'SD': 'bg-slate-600',
    'AF': 'bg-blue-600',
    'AP': 'bg-blue-600',
    'DR': 'bg-slate-600',
    'BS': 'bg-slate-700',
    'TK': 'bg-slate-500',
};
function getTesterAvatarColor(initials, name) {
    if (testerColorPalette[initials]) return testerColorPalette[initials];
    const colors = ['bg-slate-600', 'bg-blue-600', 'bg-slate-700', 'bg-slate-500', 'bg-blue-700'];
    const idx = (name?.charCodeAt(0) ?? 0) % colors.length;
    return colors[idx];
}

// ─── Delete Modal State ──────────────────────────────────────────
const showDeleteModal = ref(false);
const periodToDelete = ref(null);

function openDeleteModal(period) {
    periodToDelete.value = period;
    showDeleteModal.value = true;
}

function confirmDelete() {
    if (!periodToDelete.value) return;
    router.delete(route('admin.periods.destroy', periodToDelete.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            showFlash('Periode pengecekan berhasil dihapus.');
        },
    });
}

// ─── Status Toggle (Aktif / Nonaktif) ─────────────────────────────
const updatingPeriodId = ref(null);

function togglePeriodStatus(period) {
    const nextStatus = period.status === 'proses' ? 'selesai' : 'proses';
    updatingPeriodId.value = period.id;
    router.put(route('admin.periods.update', period.id), {
        status: nextStatus,
    }, {
        preserveScroll: true,
        onError: (errors) => {
            const err = errors.status || Object.values(errors)[0] || 'Gagal mengubah status periode.';
            showFlash(err, 'error');
        },
        onFinish: () => {
            updatingPeriodId.value = null;
        }
    });
}
</script>

<template>
    <Head title="Periode Pengecekan - MAJADIGI CHECKER" />

    <AdminLayout
        title="Periode Pengecekan"
        subtitle="Atur periode dan distribusi layanan"
    >
        <!-- ── Flash Notification ──────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 translate-y-4"
        >
            <div
                v-if="flashMsg"
                :class="[
                    'fixed bottom-[24px] right-[24px] z-[100] flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-lg text-sm font-semibold border backdrop-blur-sm transition-all max-w-md overflow-hidden',
                    flashType === 'success'
                        ? 'bg-emerald-50/95 border-emerald-200 text-emerald-800 shadow-emerald-900/10'
                        : 'bg-red-50/95 border-red-200 text-red-800 shadow-red-900/10'
                ]"
            >
                <div class="flex items-center gap-2.5 relative z-10">
                    <svg v-if="flashType === 'success'" class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg v-else class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>{{ flashMsg }}</span>
                </div>

                <!-- Close Button -->
                <button
                    type="button"
                    @click="closeFlash"
                    class="p-1 rounded-xl transition-colors focus:outline-none shrink-0 relative z-10"
                    :class="flashType === 'success'
                        ? 'text-emerald-500 hover:text-emerald-800 hover:bg-emerald-100/70'
                        : 'text-red-500 hover:text-red-800 hover:bg-red-100/70'"
                    aria-label="Tutup notifikasi"
                    title="Tutup"
                >
                    <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>

                <!-- Progress Bar -->
                <div
                    class="absolute bottom-0 left-0 h-1 transition-all ease-linear"
                    :class="flashType === 'success' ? 'bg-emerald-500/40' : 'bg-red-500/40'"
                    :style="{
                        width: progressWidth + '%',
                        transitionDuration: progressWidth === 100 ? '0ms' : `${flashDuration}ms`
                    }"
                ></div>
            </div>
        </Transition>

        <!-- ── Main Card Container ──────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">

            <!-- Card Header / Toolbar -->
            <div class="px-6 py-4 flex items-center justify-between gap-3 border-b border-slate-100">
                <h2 class="text-sm font-bold text-slate-800 truncate">
                    {{ periods.length }} Periode terdaftar
                </h2>
                <Link
                    :href="route('admin.periods.create')"
                    id="btn-buat-periode"
                    class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs shadow-sm transition-all duration-200 shrink-0 cursor-pointer"
                >
                    <span class="text-sm leading-none font-bold">+</span>
                    <span>Buat Periode</span>
                </Link>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-sm min-w-[620px]">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Periode</th>
                            <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Tester</th>
                            <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Progress</th>
                            <th class="text-center px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr
                            v-for="period in periods"
                            :key="period.id"
                            class="hover:bg-slate-50/70 transition-colors group"
                        >
                            <!-- Periode -->
                            <td class="px-5 py-3.5 text-left">
                                <Link
                                    :href="route('admin.periods.show', period.id)"
                                    class="inline-block text-left group-hover:text-blue-600 transition-colors"
                                >
                                    <p class="font-semibold text-slate-800 leading-tight group-hover:text-blue-700">
                                        {{ period.period_title }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-0.5 font-normal">
                                        {{ period.created_at_label }}
                                    </p>
                                </Link>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-5 py-3.5 text-center">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold uppercase tracking-wider',
                                        period.status === 'proses'
                                            ? 'bg-blue-50 text-[#1e3a8a] border border-blue-200/80 shadow-2xs'
                                            : 'bg-slate-100 text-slate-500 border border-slate-200/80'
                                    ]"
                                >
                                    <span
                                        class="w-1.5 h-1.5 rounded-full"
                                        :class="period.status === 'proses' ? 'bg-[#1e3a8a] animate-pulse' : 'bg-slate-400'"
                                    ></span>
                                    {{ period.status === 'proses' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>

                            <!-- Tester Avatars -->
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center -space-x-2">
                                    <div
                                        v-for="tester in period.testers"
                                        :key="tester.id"
                                        :title="tester.email ? `${tester.name} (${tester.email})` : tester.name"
                                        class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold ring-2 ring-white shadow-sm cursor-default transition-transform hover:scale-110 hover:z-10"
                                        :class="getTesterAvatarColor(tester.initials, tester.name)"
                                    >
                                        {{ tester.initials }}
                                    </div>
                                    <span
                                        v-if="!period.testers || period.testers.length === 0"
                                        class="text-xs text-slate-400 italic"
                                    >
                                        Belum ada tester
                                    </span>
                                </div>
                            </td>

                            <!-- Progress -->
                            <td class="px-5 py-3.5 text-center">
                                <div class="w-44 mx-auto text-center">
                                    <p class="text-xs font-medium text-slate-400 mb-1">
                                        <span class="text-slate-700 font-semibold">{{ period.checked_services }}/{{ period.total_services }}</span> layanan
                                    </p>
                                    <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="period.progress_percent === 100 ? 'bg-blue-600' : 'bg-blue-400'"
                                            :style="{ width: `${period.progress_percent}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Aksi (Toggle Aktif/Nonaktif, Show, Edit, Hapus) -->
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Toggle Switch Status (Aktif / Nonaktif) -->
                                    <button
                                        type="button"
                                        role="switch"
                                        :aria-checked="period.status === 'proses'"
                                        @click="togglePeriodStatus(period)"
                                        :disabled="updatingPeriodId === period.id"
                                        :title="period.status === 'proses' ? 'Klik untuk menonaktifkan periode' : 'Klik untuk mengaktifkan periode'"
                                        class="relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-[#1e3a8a] focus:ring-offset-1 disabled:opacity-50"
                                        :class="period.status === 'proses' ? 'bg-[#1e3a8a]' : 'bg-slate-300'"
                                    >
                                        <span
                                            class="pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-xs ring-0 transition duration-200 ease-in-out"
                                            :class="period.status === 'proses' ? 'translate-x-4' : 'translate-x-0'"
                                        />
                                    </button>

                                    <!-- Divider Halus -->
                                    <div class="h-4 w-px bg-slate-200"></div>

                                    <!-- Detail (Show) -->
                                    <Link
                                        :href="route('admin.periods.show', period.id)"
                                        title="Lihat detail"
                                        aria-label="Lihat detail"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-100/70 transition-all active:scale-95"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </Link>

                                    <!-- Edit -->
                                    <Link
                                        :href="route('admin.periods.edit', period.id)"
                                        title="Edit periode"
                                        aria-label="Edit periode"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-amber-600 hover:bg-amber-100/70 transition-all active:scale-95"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/>
                                        </svg>
                                    </Link>

                                    <!-- Hapus -->
                                    <button
                                        @click="openDeleteModal(period)"
                                        title="Hapus periode"
                                        aria-label="Hapus periode"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-100/70 transition-all active:scale-95 cursor-pointer"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty State -->
                        <tr v-if="periods.length === 0">
                            <td colspan="5" class="py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center mb-3 text-slate-400">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <rect x="3" y="4" width="18" height="18" rx="2" stroke-linecap="round"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 2v4M8 2v4M3 10h18"/>
                                        </svg>
                                    </div>
                                    <p class="font-semibold text-slate-600 text-sm">Belum ada periode pengecekan</p>
                                    <p class="text-xs text-slate-400 mt-0.5">Klik tombol "+ Buat Periode" untuk memulai periode baru.</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- Modal: Konfirmasi Hapus (Identik Manajemen User)      -->
        <!-- ════════════════════════════════════════════════════ -->
        <Teleport to="body">
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="transition-all duration-150 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showDeleteModal && periodToDelete" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-sm p-6 sm:p-7 z-10 text-center">
                        
                        <!-- KONDISI 3: Periode yang sudah memiliki HASIL pengecekan -> FORCE DELETE CONFIRMATION -->
                        <template v-if="periodToDelete.checked_services > 0 || periodToDelete.has_results">
                            <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mx-auto mb-4 border border-rose-200 shadow-xs">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Hapus Permanen Histori?</h3>
                            <p class="text-sm text-slate-500 mt-2 leading-relaxed mb-6">
                                Periode <span class="font-semibold text-slate-700">"{{ periodToDelete.period_title }}"</span> sudah memiliki <span class="font-bold text-slate-700">{{ periodToDelete.checked_services }} hasil pengecekan</span>. 
                                <br><br>
                                <span class="text-rose-600 font-semibold">Peringatan:</span> Menghapus periode ini akan <b>menghapus permanen seluruh data riwayat pengecekan</b> di dalamnya. Apakah Anda yakin?
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="showDeleteModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete"
                                    class="flex-1 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-sm font-bold text-white transition-all shadow-md shadow-rose-600/20 active:scale-95 cursor-pointer flex justify-center items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z" />
                                    </svg>
                                    Hapus
                                </button>
                            </div>
                        </template>

                        <!-- KONDISI 2: Periode yang sudah ada tester tapi BELUM melakukan pengecekan -> MASIH BISA DIHAPUS DENGAN CONFIRMATION WARNING -->
                        <template v-else-if="periodToDelete.testers && periodToDelete.testers.length > 0">
                            <div class="w-16 h-16 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mx-auto mb-4 border border-amber-200 shadow-xs">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Hapus Periode?</h3>
                            <p class="text-sm text-slate-500 mt-2 leading-relaxed mb-6">
                                Periode ini sudah memiliki <span class="font-bold text-slate-700">{{ periodToDelete.testers.length }} tester</span> yang ditugaskan. Apakah Anda yakin ingin menghapus periode? Seluruh penugasan layanan di dalamnya akan ikut dihapus.
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="showDeleteModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete"
                                    class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold shadow-sm transition-all cursor-pointer"
                                >
                                    Ya, Hapus
                                </button>
                            </div>
                        </template>

                        <!-- KONDISI STANDAR: Belum ada tester dan belum ada hasil -->
                        <template v-else>
                            <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-4 border border-rose-100 shadow-xs">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-800">Hapus Periode?</h3>
                            <p class="text-sm text-slate-500 mt-2 leading-relaxed mb-6">
                                Periode <span class="font-semibold text-slate-700">"{{ periodToDelete.period_title }}"</span> dan seluruh penugasan layanan di dalamnya akan dihapus permanen.
                            </p>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    @click="showDeleteModal = false"
                                    class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all cursor-pointer"
                                >
                                    Batal
                                </button>
                                <button
                                    type="button"
                                    @click="confirmDelete"
                                    class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold shadow-sm transition-all cursor-pointer"
                                >
                                    Ya, Hapus
                                </button>
                            </div>
                        </template>

                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
