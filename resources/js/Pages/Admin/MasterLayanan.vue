<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

// ─── Props dari controller ────────────────────────────────────
const props = defineProps({
    services: { type: Object, default: () => ({ data: [] }) },
    stats:    { type: Object, default: () => ({ total: 0, aktif: 0, nonaktif: 0 }) },
    search:   { type: String, default: '' },
    status:   { type: String, default: '' },
    level:    { type: String, default: '' },
    levels:   { type: Array,  default: () => [] },
});

// ─── State modal ─────────────────────────────────────────────
const showAddModal    = ref(false);
const showEditModal   = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const showHistoryModal = ref(false);
const selectedService = ref(null);

// ─── Search & Filters (debounced via Inertia) ──────────────
const searchQuery = ref(props.search || '');
const filterStatus = ref(props.status ?? '');
const filterLevel = ref(props.level ?? '');

let searchTimer = null;
watch([searchQuery, filterStatus, filterLevel], ([newSearch, newStatus, newLevel]) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.services.index'), { search: newSearch, status: newStatus, level: newLevel }, {
            preserveState: true,
            replace: true,
        });
    }, 350);
});

// ─── Flash message ───────────────────────────────────────────
const flash = ref('');
const flashType = ref('success'); // 'success' | 'error'
let flashTimer = null;

function closeFlash() {
    if (flashTimer) clearTimeout(flashTimer);
    flash.value = '';
}

function showFlash(msg, type = 'success') {
    if (flashTimer) clearTimeout(flashTimer);
    flash.value = msg;
    flashType.value = type;
    flashTimer = setTimeout(() => { flash.value = ''; }, 4000);
}

// ─── Add Form ─────────────────────────────────────────────────
const addForm = useForm({
    service_name: '',
    category:     '',
    level:        '',
    service_url:  '',
    is_active:    true,
    notes:        '',
    deskripsi_layanan: '',
    tester_instruction: '',
});
function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    showAddModal.value = true;
}
function submitAdd() {
    addForm.post(route('admin.services.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            showFlash('Layanan berhasil ditambahkan.');
        },
    });
}

// ─── Edit Form ────────────────────────────────────────────────
const editForm = useForm({
    service_name: '',
    category:     '',
    level:        '',
    service_url:  '',
    is_active:    true,
    notes:        '',
    deskripsi_layanan: '',
    tester_instruction: '',
});
function openEditModal(service) {
    selectedService.value = service;
    editForm.service_name = service.service_name;
    editForm.category     = service.category;
    editForm.level        = service.level ?? '';
    editForm.service_url  = service.service_url;
    editForm.is_active    = !!service.is_active;
    editForm.notes        = service.notes;
    editForm.deskripsi_layanan = service.deskripsi_layanan ?? '';
    editForm.tester_instruction = service.tester_instruction ?? '';
    editForm.clearErrors();
    showEditModal.value = true;
}
function submitEdit() {
    editForm.put(route('admin.services.update', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            showFlash('Data layanan berhasil diperbarui.');
        },
    });
}

// ─── Detail Modal ─────────────────────────────────────────────
function openDetailModal(service) {
    selectedService.value = service;
    showDetailModal.value = true;
}

// ─── History Modal ────────────────────────────────────────────
const serviceHistory = ref([]);
const isLoadingHistory = ref(false);

async function openHistoryModal(service) {
    selectedService.value = service;
    showHistoryModal.value = true;
    isLoadingHistory.value = true;
    serviceHistory.value = [];
    
    try {
        const response = await axios.get(route('admin.services.history', service.id));
        serviceHistory.value = response.data;
    } catch (error) {
        showFlash('Gagal memuat riwayat pengecekan.', 'error');
    } finally {
        isLoadingHistory.value = false;
    }
}

// ─── Delete ───────────────────────────────────────────────────
function openDeleteModal(service) {
    selectedService.value = service;
    showDeleteModal.value = true;
}
function confirmDelete() {
    router.delete(route('admin.services.destroy', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            showFlash('Layanan berhasil dihapus.');
        },
    });
}
</script>

<template>
    <Head title="Master Layanan - MAJADIGI CHECKER" />
    <AdminLayout
        title="Master Layanan"
        subtitle="Kelola master data layanan digital"
    >

        <!-- ── Flash Notification ──────────────────────────────── -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-2 translate-x-1"
            enter-to-class="opacity-100 translate-y-0 translate-x-0"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 translate-x-0"
            leave-to-class="opacity-0 -translate-y-2 translate-x-1"
        >
            <div
                v-if="flash"
                :class="[
                    'fixed top-5 right-5 z-[100] flex items-center justify-between gap-3 px-4 py-3 rounded-2xl shadow-lg text-sm font-semibold border backdrop-blur-sm transition-all max-w-md',
                    flashType === 'success'
                        ? 'bg-emerald-50/95 border-emerald-200 text-emerald-800 shadow-emerald-900/10'
                        : 'bg-red-50/95 border-red-200 text-red-800 shadow-red-900/10'
                ]"
            >
                <div class="flex items-center gap-2.5">
                    <svg v-if="flashType === 'success'" class="w-4 h-4 shrink-0 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <svg v-else class="w-4 h-4 shrink-0 text-red-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    <span>{{ flash }}</span>
                </div>

                <!-- Close Button -->
                <button
                    type="button"
                    @click="closeFlash"
                    class="p-1 rounded-xl transition-colors focus:outline-none shrink-0"
                    :class="flashType === 'success'
                        ? 'text-emerald-500 hover:text-emerald-800 hover:bg-emerald-100/70'
                        : 'text-red-500 hover:text-red-800 hover:bg-red-100/70'"
                    aria-label="Tutup notifikasi"
                >
                    <svg class="w-4 h-4 stroke-[2.2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </Transition>

        <!-- ── Stats Cards ───────────────────────────────────── -->
        <div class="grid grid-cols-3 gap-2.5 sm:gap-4 mb-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider truncate">Total Layanan</p>
                </div>
                <span class="text-xl sm:text-3xl font-extrabold text-slate-800">{{ stats.total }}</span>
            </div>
            <div class="bg-emerald-50 rounded-2xl border border-emerald-100 shadow-sm p-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-emerald-600 uppercase tracking-wider truncate">Aktif</p>
                </div>
                <span class="text-xl sm:text-3xl font-extrabold text-emerald-700">{{ stats.aktif }}</span>
            </div>
            <div class="bg-rose-50 rounded-2xl border border-rose-100 shadow-sm p-3 sm:px-6 sm:py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-1">
                <div>
                    <p class="text-[10px] sm:text-xs font-bold text-rose-600 uppercase tracking-wider truncate">Non-Aktif</p>
                </div>
                <span class="text-xl sm:text-3xl font-extrabold text-rose-700">{{ stats.nonaktif }}</span>
            </div>
        </div>

        <!-- ── Toolbar ───────────────────────────────────────── -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <!-- Search & Filters -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full sm:w-auto">
                <div class="relative w-full sm:w-72">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                        </svg>
                    </div>
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari layanan atau kategori..."
                        class="block w-full pl-10 pr-10 py-2.5 bg-white border border-slate-200 rounded-xl text-sm placeholder:text-slate-400 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none transition-shadow"
                    />
                    <button
                        v-if="searchQuery"
                        @click="searchQuery = ''"
                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                        title="Hapus pencarian"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <select v-model="filterStatus" class="w-full sm:w-36 py-2.5 px-3 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-slate-700">
                    <option value="">Semua Status</option>
                    <option value="1">Aktif</option>
                    <option value="0">Non-Aktif</option>
                </select>
                <select v-model="filterLevel" class="w-full sm:w-36 py-2.5 px-3 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none text-slate-700">
                    <option value="">Semua Level</option>
                    <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                </select>
            </div>
            <!-- Add Button -->
            <button
                @click="openAddModal"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-md shadow-blue-600/20 transition-all active:scale-95"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Layanan
            </button>
        </div>

        <!-- ── Main Table ────────────────────────────────────── -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200">
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider w-16">No</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Nama Layanan</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Kategori</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Level</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Keterangan</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-3 px-5 text-[11px] font-bold text-slate-500 uppercase tracking-wider text-center w-24">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        <tr v-if="!services.data || services.data.length === 0">
                            <td colspan="7" class="py-12 px-5 text-center">
                                <div class="flex flex-col items-center justify-center text-slate-400">
                                    <svg class="w-12 h-12 mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2a4 4 0 014-4h0a4 4 0 014 4v2m-6-10h6m-6 4h6"/>
                                        <rect x="2" y="3" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                                        <path stroke-linecap="round" d="M8 21h8M12 17v4" stroke-width="1.5"/>
                                    </svg>
                                    <p class="font-medium text-slate-500">Tidak ada data layanan</p>
                                    <p class="text-xs mt-1">Coba sesuaikan pencarian Anda atau tambah layanan baru.</p>
                                </div>
                            </td>
                        </tr>
                        <tr
                            v-for="(svc, index) in services.data"
                            :key="svc.id"
                            class="hover:bg-slate-50/50 transition-colors group"
                        >
                            <td class="py-3.5 px-5 text-slate-500 font-medium align-middle">
                                {{ (services.current_page - 1) * services.per_page + index + 1 }}
                            </td>
                            <td class="py-3.5 px-5 align-middle">
                                <div class="font-bold text-slate-800">{{ svc.service_name }}</div>
                                <a v-if="svc.service_url" :href="svc.service_url" target="_blank" class="text-xs text-blue-500 hover:underline inline-flex items-center gap-1 mt-0.5 truncate max-w-[200px]">
                                    {{ svc.service_url }}
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </td>
                            <td class="py-3.5 px-5 align-middle">
                                <span class="text-sm font-medium text-slate-600">{{ svc.category || '-' }}</span>
                            </td>
                            <td class="py-3.5 px-5 align-middle">
                                <span class="text-sm font-medium text-slate-600">{{ svc.level || '-' }}</span>
                            </td>
                            <td class="py-3.5 px-5 align-middle hidden md:table-cell max-w-xs">
                                <p class="text-xs text-slate-500 line-clamp-2" :title="svc.notes">{{ svc.notes || '-' }}</p>
                            </td>
                            <td class="py-3.5 px-5 align-middle">
                                <span
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                                    :class="svc.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="svc.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ svc.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-5 text-center align-middle">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button @click="openHistoryModal(svc)" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Riwayat Pengecekan">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </button>
                                    <button @click="openDetailModal(svc)" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Detail">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                    <button @click="openEditModal(svc)" class="p-1.5 text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button @click="openDeleteModal(svc)" class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Hapus">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- ── Pagination ────────────────────────────────────── -->
            <div v-if="services.links && services.links.length > 3" class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div>
                    <p class="text-sm text-slate-600">
                        Menampilkan <span class="font-bold text-slate-800">{{ services.from }}</span> sampai <span class="font-bold text-slate-800">{{ services.to }}</span> dari <span class="font-bold text-slate-800">{{ services.total }}</span> data
                    </p>
                </div>
                <div>
                    <nav class="relative z-0 inline-flex shadow-sm rounded-xl overflow-hidden" aria-label="Pagination">
                        <template v-for="(link, i) in services.links" :key="i">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                v-html="link.label"
                                :class="[
                                    'relative inline-flex items-center px-4 py-2 border border-slate-200 text-sm font-medium transition-colors',
                                    link.active ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white text-slate-600 hover:bg-slate-50',
                                ]"
                            />
                            <span
                                v-else
                                v-html="link.label"
                                class="relative inline-flex items-center px-4 py-2 border border-slate-200 bg-slate-50 text-sm font-medium text-slate-400"
                            ></span>
                        </template>
                    </nav>
                </div>
            </div>
        </div>

        <!-- ── Modal Wrapper Utility ───────────────────────────── -->
        <!-- Overlay -->
        <Transition
            enter-active-class="transition-opacity duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-opacity duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showAddModal || showEditModal || showDetailModal || showDeleteModal || showHistoryModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-[100]"></div>
        </Transition>

        <!-- ── MODAL: Tambah Layanan ──────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showAddModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-bold text-slate-800">Tambah Layanan</h3>
                        <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <form @submit.prevent="submitAdd" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Layanan <span class="text-rose-500">*</span></label>
                                <input v-model="addForm.service_name" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Majadigi SSO" required />
                                <p v-if="addForm.errors.service_name" class="mt-1 text-xs text-rose-500">{{ addForm.errors.service_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                                <input v-model="addForm.category" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Aplikasi Web" />
                                <p v-if="addForm.errors.category" class="mt-1 text-xs text-rose-500">{{ addForm.errors.category }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Level</label>
                                <select v-model="addForm.level" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 text-slate-700 bg-white">
                                    <option value="">Pilih Level</option>
                                    <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                                </select>
                                <p v-if="addForm.errors.level" class="mt-1 text-xs text-rose-500">{{ addForm.errors.level }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">URL Layanan</label>
                                <input v-model="addForm.service_url" type="url" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="https://..." />
                                <p v-if="addForm.errors.service_url" class="mt-1 text-xs text-rose-500">{{ addForm.errors.service_url }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi Layanan (Untuk Tester)</label>
                                <textarea v-model="addForm.deskripsi_layanan" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan fungsi layanan ini secara singkat..."></textarea>
                                <p v-if="addForm.errors.deskripsi_layanan" class="mt-1 text-xs text-rose-500">{{ addForm.errors.deskripsi_layanan }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Instruksi Khusus Tester</label>
                                <textarea v-model="addForm.tester_instruction" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Contoh: Gunakan akun demo admin@test.com password: 123..."></textarea>
                                <p v-if="addForm.errors.tester_instruction" class="mt-1 text-xs text-rose-500">{{ addForm.errors.tester_instruction }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Keterangan / Notes Lainnya (Opsional)</label>
                                <textarea v-model="addForm.notes" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                <p v-if="addForm.errors.notes" class="mt-1 text-xs text-rose-500">{{ addForm.errors.notes }}</p>
                            </div>
                            <div class="flex items-center gap-3 mt-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="addForm.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-semibold text-slate-700">Status Aktif</span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showAddModal = false" class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 transition-colors">Batal</button>
                        <button type="button" @click="submitAdd" :disabled="addForm.processing" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all disabled:opacity-70 flex items-center gap-2">
                            <svg v-if="addForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── MODAL: Edit Layanan ──────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showEditModal" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-bold text-slate-800">Edit Layanan</h3>
                        <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-6 overflow-y-auto">
                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Nama Layanan <span class="text-rose-500">*</span></label>
                                <input v-model="editForm.service_name" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" required />
                                <p v-if="editForm.errors.service_name" class="mt-1 text-xs text-rose-500">{{ editForm.errors.service_name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Kategori</label>
                                <input v-model="editForm.category" type="text" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" />
                                <p v-if="editForm.errors.category" class="mt-1 text-xs text-rose-500">{{ editForm.errors.category }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Level</label>
                                <select v-model="editForm.level" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 text-slate-700 bg-white">
                                    <option value="">Pilih Level</option>
                                    <option v-for="lvl in levels" :key="lvl" :value="lvl">{{ lvl }}</option>
                                </select>
                                <p v-if="editForm.errors.level" class="mt-1 text-xs text-rose-500">{{ editForm.errors.level }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">URL Layanan</label>
                                <input v-model="editForm.service_url" type="url" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" />
                                <p v-if="editForm.errors.service_url" class="mt-1 text-xs text-rose-500">{{ editForm.errors.service_url }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Deskripsi Layanan (Untuk Tester)</label>
                                <textarea v-model="editForm.deskripsi_layanan" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                <p v-if="editForm.errors.deskripsi_layanan" class="mt-1 text-xs text-rose-500">{{ editForm.errors.deskripsi_layanan }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Instruksi Khusus Tester</label>
                                <textarea v-model="editForm.tester_instruction" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                <p v-if="editForm.errors.tester_instruction" class="mt-1 text-xs text-rose-500">{{ editForm.errors.tester_instruction }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Keterangan / Notes Lainnya</label>
                                <textarea v-model="editForm.notes" rows="2" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                                <p v-if="editForm.errors.notes" class="mt-1 text-xs text-rose-500">{{ editForm.errors.notes }}</p>
                            </div>
                            <div class="flex items-center gap-3 mt-2">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" v-model="editForm.is_active" class="sr-only peer">
                                    <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                    <span class="ml-3 text-sm font-semibold text-slate-700">Status Aktif</span>
                                </label>
                            </div>
                        </form>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showEditModal = false" class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 hover:text-slate-800 transition-colors">Batal</button>
                        <button type="button" @click="submitEdit" :disabled="editForm.processing" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all disabled:opacity-70 flex items-center gap-2">
                            <svg v-if="editForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Perubahan
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── MODAL: Detail Layanan ──────────────────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showDetailModal && selectedService" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-lg font-bold text-slate-800">Detail Layanan</h3>
                        <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nama Layanan</p>
                                <p class="text-sm font-bold text-slate-800">{{ selectedService.service_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Kategori</p>
                                <p class="text-sm text-slate-700">{{ selectedService.category || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Level</p>
                                <p class="text-sm text-slate-700">{{ selectedService.level || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">URL Layanan</p>
                                <a v-if="selectedService.service_url" :href="selectedService.service_url" target="_blank" class="text-sm text-blue-600 hover:underline break-all">{{ selectedService.service_url }}</a>
                                <p v-else class="text-sm text-slate-500">-</p>
                            </div>
                            <div v-if="selectedService.deskripsi_layanan">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Layanan</p>
                                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ selectedService.deskripsi_layanan }}</p>
                                </div>
                            </div>
                            <div v-if="selectedService.tester_instruction">
                                <p class="text-xs font-semibold text-rose-500 uppercase tracking-wider mb-1 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Instruksi Khusus Tester
                                </p>
                                <div class="p-3 bg-rose-50 rounded-xl border border-rose-100">
                                    <p class="text-sm text-rose-800 whitespace-pre-wrap font-medium">{{ selectedService.tester_instruction }}</p>
                                </div>
                            </div>
                            <div v-if="selectedService.notes">
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Keterangan / Notes</p>
                                <div class="p-3 bg-blue-50/50 rounded-xl border border-blue-100">
                                    <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ selectedService.notes }}</p>
                                </div>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Status</p>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold" :class="selectedService.is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'">
                                    <span class="w-1.5 h-1.5 rounded-full" :class="selectedService.is_active ? 'bg-emerald-500' : 'bg-slate-400'"></span>
                                    {{ selectedService.is_active ? 'Aktif' : 'Non-Aktif' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end">
                        <button type="button" @click="showDetailModal = false" class="px-5 py-2.5 rounded-xl font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-100 transition-colors">Tutup</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── MODAL: Konfirmasi Hapus ───────────────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showDeleteModal && selectedService" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-3xl shadow-xl w-full max-w-sm overflow-hidden text-center p-8">
                    <div class="w-16 h-16 rounded-2xl bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Hapus Layanan?</h3>
                    <p class="text-sm text-slate-500 mb-6">Anda yakin ingin menghapus data layanan <span class="font-bold text-slate-700">"{{ selectedService.service_name }}"</span>? Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="flex flex-col sm:flex-row gap-3">
                        <button type="button" @click="showDeleteModal = false" class="w-full px-5 py-3 rounded-xl font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="button" @click="confirmDelete" class="w-full px-5 py-3 rounded-xl font-semibold text-white bg-rose-500 hover:bg-rose-600 shadow-md shadow-rose-500/20 transition-colors">Ya, Hapus</button>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- ── MODAL: Riwayat Pengecekan ────────────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showHistoryModal && selectedService" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Riwayat Pengecekan</h3>
                            <p class="text-xs text-slate-500">{{ selectedService.service_name }}</p>
                        </div>
                        <button @click="showHistoryModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>
                    <div class="p-6 overflow-y-auto bg-slate-50/30">
                        <div v-if="isLoadingHistory" class="flex flex-col items-center justify-center py-10">
                            <svg class="animate-spin w-8 h-8 text-blue-500 mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <p class="text-sm font-medium text-slate-500">Memuat riwayat...</p>
                        </div>
                        
                        <div v-else-if="serviceHistory.length === 0" class="flex flex-col items-center justify-center py-10 text-center">
                            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-3">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <p class="text-base font-bold text-slate-700">Belum Ada Riwayat</p>
                            <p class="text-sm text-slate-500 mt-1">Layanan ini belum pernah dicek oleh tester.</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div v-for="log in serviceHistory" :key="log.id" class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm relative overflow-hidden group hover:border-blue-200 transition-colors">
                                <!-- Status indicator line -->
                                <div class="absolute left-0 top-0 bottom-0 w-1" :class="log.status === 'normal' ? 'bg-emerald-500' : (log.status === 'gangguan' ? 'bg-rose-500' : 'bg-slate-300')"></div>
                                
                                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                                    <div>
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ log.period_date }}</span>
                                            <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                                            <span class="text-xs text-slate-500">{{ log.checked_at }}</span>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800 mb-0.5">Tester: {{ log.tester }}</p>
                                        <p class="text-sm text-slate-600 mt-2 p-3 bg-slate-50 rounded-lg border border-slate-100 whitespace-pre-wrap">{{ log.description || 'Tidak ada catatan.' }}</p>
                                    </div>
                                    <div class="shrink-0">
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold shadow-sm" :class="log.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : (log.status === 'gangguan' ? 'bg-rose-50 text-rose-700 border border-rose-100' : 'bg-slate-100 text-slate-600 border border-slate-200')">
                                            <span class="w-1.5 h-1.5 rounded-full" :class="log.status === 'normal' ? 'bg-emerald-500' : (log.status === 'gangguan' ? 'bg-rose-500' : 'bg-slate-400')"></span>
                                            {{ log.status === 'normal' ? 'NORMAL' : (log.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
