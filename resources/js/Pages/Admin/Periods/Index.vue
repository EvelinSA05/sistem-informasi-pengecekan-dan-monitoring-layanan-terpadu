<script setup>
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    periods: { type: Array, default: () => [] },
    flash:   { type: Object, default: () => ({}) },
});

// ─── Flash Notification ──────────────────────────────────────────
const flashMsg = ref(props.flash?.success || props.flash?.error || '');
const flashType = ref(props.flash?.error ? 'error' : 'success');

function showFlash(msg, type = 'success') {
    flashMsg.value = msg;
    flashType.value = type;
    setTimeout(() => { flashMsg.value = ''; }, 3500);
}

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

// ─── Edit Modal State ────────────────────────────────────────────
const showEditModal = ref(false);
const selectedPeriod = ref(null);
const editForm = useForm({
    status: 'proses',
});

function openEditModal(period) {
    selectedPeriod.value = period;
    editForm.status = period.status;
    showEditModal.value = true;
}

function submitEdit() {
    editForm.put(route('admin.periods.update', selectedPeriod.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            showFlash('Status periode berhasil diperbarui.');
        },
    });
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
            enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="flashMsg"
                :class="[
                    'fixed top-5 right-5 z-[100] flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold',
                    flashType === 'success'
                        ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
                        : 'bg-red-50 border border-red-200 text-red-700'
                ]"
            >
                <svg v-if="flashType === 'success'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ flashMsg }}
            </div>
        </Transition>

        <!-- ── Main Card Container ──────────────────────────────── -->
        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">

            <!-- Card Header / Toolbar -->
            <div class="px-8 py-6 flex items-center justify-between border-b border-slate-100">
                <div>
                    <h2 class="text-base font-bold text-slate-800">
                        {{ periods.length }} Periode terdaftar
                    </h2>
                </div>
                <Link
                    :href="route('admin.periods.create')"
                    id="btn-buat-periode"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white font-bold text-sm shadow-md shadow-blue-900/10 hover:shadow-lg hover:shadow-blue-900/20 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
                >
                    <span class="text-base leading-none font-extrabold">+</span>
                    Buat Periode
                </Link>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/40 text-[11px] font-bold text-slate-400 uppercase tracking-wider">
                            <th class="py-4 px-8">PERIODE</th>
                            <th class="py-4 px-6">STATUS</th>
                            <th class="py-4 px-6">TESTER</th>
                            <th class="py-4 px-6">PROGRESS</th>
                            <th class="py-4 px-8 text-right">AKSI</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr
                            v-for="period in periods"
                            :key="period.id"
                            class="hover:bg-slate-50/70 transition-colors group"
                        >
                            <!-- Periode -->
                            <td class="py-5 px-8">
                                <Link
                                    :href="route('admin.periods.show', period.id)"
                                    class="block group-hover:text-blue-600 transition-colors"
                                >
                                    <p class="font-bold text-slate-800 leading-tight group-hover:text-blue-700">
                                        {{ period.period_title }}
                                    </p>
                                    <p class="text-xs text-slate-400 mt-1 font-medium">
                                        {{ period.created_at_label }}
                                    </p>
                                </Link>
                            </td>

                            <!-- Status -->
                            <td class="py-5 px-6">
                                <!-- Status PROSES -->
                                <span
                                    v-if="period.status === 'proses'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-[#1e3a8a] text-white shadow-sm"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-cyan-300 animate-pulse"></span>
                                    PROSES
                                </span>

                                <!-- Status SELESAI -->
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold tracking-wide uppercase bg-slate-100 border border-slate-200/80 text-slate-600"
                                >
                                    <svg class="w-3 h-3 text-slate-500 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    SELESAI
                                </span>
                            </td>

                            <!-- Tester Avatars -->
                            <td class="py-5 px-6">
                                <div class="flex items-center -space-x-2">
                                    <div
                                        v-for="tester in period.testers"
                                        :key="tester.id"
                                        :title="`${tester.name} (${tester.bidang || 'Layanan Publik'})`"
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
                            <td class="py-5 px-6">
                                <div class="w-48">
                                    <p class="text-xs font-medium text-slate-400 mb-1.5">
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
                            <td class="py-5 px-8 text-right">
                                <div class="inline-flex items-center gap-1.5 justify-end">
                                    <!-- Show Detail -->
                                    <Link
                                        :href="route('admin.periods.show', period.id)"
                                        title="Lihat Detail"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </Link>

                                    <!-- Edit Status -->
                                    <button
                                        @click="openEditModal(period)"
                                        title="Ubah Status"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-500 hover:text-amber-600 hover:bg-amber-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <!-- Hapus -->
                                    <button
                                        @click="openDeleteModal(period)"
                                        title="Hapus Periode"
                                        class="w-8 h-8 rounded-xl flex items-center justify-center text-slate-500 hover:text-rose-600 hover:bg-rose-50 transition-colors"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
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

        <!-- ─── Modal Edit Status ─────────────────────────────── -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showEditModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
                @click.self="showEditModal = false"
            >
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                    <div class="px-6 pt-6 pb-4 border-b border-slate-100">
                        <h3 class="text-base font-bold text-slate-800">Ubah Status Periode</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ selectedPeriod?.period_title }}</p>
                    </div>

                    <form @submit.prevent="submitEdit" class="p-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                                Status Periode
                            </label>
                            <div class="grid grid-cols-2 gap-3">
                                <label
                                    :class="[
                                        'cursor-pointer flex items-center justify-center gap-2 p-3.5 rounded-2xl border text-xs font-bold transition-all',
                                        editForm.status === 'proses'
                                            ? 'bg-blue-50 border-blue-500 text-blue-800 ring-2 ring-blue-500/20'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <input type="radio" v-model="editForm.status" value="proses" class="sr-only" />
                                    <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                                    PROSES
                                </label>
                                <label
                                    :class="[
                                        'cursor-pointer flex items-center justify-center gap-2 p-3.5 rounded-2xl border text-xs font-bold transition-all',
                                        editForm.status === 'selesai'
                                            ? 'bg-emerald-50 border-emerald-500 text-emerald-800 ring-2 ring-emerald-500/20'
                                            : 'border-slate-200 text-slate-600 hover:bg-slate-50'
                                    ]"
                                >
                                    <input type="radio" v-model="editForm.status" value="selesai" class="sr-only" />
                                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    SELESAI
                                </label>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                            <button
                                type="button"
                                @click="showEditModal = false"
                                class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 transition-colors"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                :disabled="editForm.processing"
                                class="px-5 py-2 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-xs font-bold shadow transition-all duration-150 disabled:opacity-50"
                            >
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>

        <!-- ─── Modal Konfirmasi Hapus ────────────────────────── -->
        <Transition
            enter-active-class="transition-opacity duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showDeleteModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4"
                @click.self="showDeleteModal = false"
            >
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-200 w-full max-w-md p-6 animate-in fade-in zoom-in-95 duration-200">
                    <div class="w-12 h-12 rounded-2xl bg-rose-50 border border-rose-100 text-rose-600 flex items-center justify-center mb-4">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">Hapus Periode Pengecekan?</h3>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Apakah Anda yakin ingin menghapus periode <span class="font-bold text-slate-700">{{ periodToDelete?.period_title }}</span>? Seluruh data distribusi layanan dan riwayat pengecekan pada periode ini akan ikut terhapus.
                    </p>
                    <div class="flex items-center justify-end gap-2.5 mt-6 pt-4 border-t border-slate-100">
                        <button
                            type="button"
                            @click="showDeleteModal = false"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-500 hover:bg-slate-100 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="confirmDelete"
                            class="px-5 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold shadow-md shadow-rose-600/20 transition-all duration-150"
                        >
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
