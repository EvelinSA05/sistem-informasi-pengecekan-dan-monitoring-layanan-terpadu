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
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">

            <!-- Card Header / Toolbar -->
            <div class="p-4 sm:px-8 sm:py-6 flex items-center justify-between gap-3 border-b border-slate-100">
                <div>
                    <h2 class="text-sm sm:text-base font-bold text-slate-800 truncate">
                        {{ periods.length }} Periode terdaftar
                    </h2>
                </div>
                <Link
                    :href="route('admin.periods.create')"
                    id="btn-buat-periode"
                    class="inline-flex items-center gap-1.5 sm:gap-2 px-3.5 sm:px-5 py-2 sm:py-2.5 rounded-xl sm:rounded-2xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white font-bold text-xs sm:text-sm shadow-md shadow-blue-900/10 hover:shadow-lg hover:shadow-blue-900/20 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0 shrink-0"
                >
                    <span class="text-base leading-none font-extrabold">+</span>
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

                            <!-- Status -->
                            <td class="px-5 py-3.5 text-center">
                                <!-- Status PROSES -->
                                <span
                                    v-if="period.status === 'proses'"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold uppercase bg-[#1e3a8a] text-white shadow-xs"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-300 animate-pulse"></span>
                                    PROSES
                                </span>

                                <!-- Status SELESAI -->
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold uppercase bg-slate-100 border border-slate-200 text-slate-600"
                                >
                                    <svg class="w-3 h-3 text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    SELESAI
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
                                            :class="period.progress_percent === 100 ? 'bg-emerald-500' : 'bg-cyan-500'"
                                            :style="{ width: `${period.progress_percent}%` }"
                                        ></div>
                                    </div>
                                </div>
                            </td>

                            <!-- Aksi (Show, Edit, Hapus) -->
                            <td class="px-5 py-3.5 text-center">
                                <div class="flex items-center justify-center gap-1.5 opacity-100 sm:opacity-0 sm:group-hover:opacity-100 transition-opacity duration-150">
                                    <!-- Detail (Show) -->
                                    <Link
                                        :href="route('admin.periods.show', period.id)"
                                        title="Lihat detail"
                                        aria-label="Lihat detail"
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-blue-600 bg-blue-50/90 sm:bg-transparent sm:text-slate-400 hover:text-blue-600 hover:bg-blue-100/70 transition-all active:scale-95"
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
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-600 bg-amber-50/90 sm:bg-transparent sm:text-amber-500 hover:text-amber-700 hover:bg-amber-100/70 transition-all active:scale-95"
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
                                        class="w-8 h-8 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50/90 sm:bg-transparent sm:text-rose-400 hover:text-rose-600 hover:bg-rose-100/70 transition-all active:scale-95"
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
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                        <div class="flex flex-col items-center text-center mb-5">
                            <div class="w-14 h-14 rounded-2xl bg-rose-100 flex items-center justify-center mb-4 text-rose-500">
                                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">Hapus Periode?</h3>
                            <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
                                Periode <span class="font-semibold text-slate-700">{{ periodToDelete.period_title }}</span> dan seluruh penugasan layanan di dalamnya akan dihapus permanen.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button
                                @click="showDeleteModal = false"
                                class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all"
                            >
                                Batal
                            </button>
                            <button
                                @click="confirmDelete"
                                class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold shadow-sm transition-all"
                            >
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AdminLayout>
</template>
