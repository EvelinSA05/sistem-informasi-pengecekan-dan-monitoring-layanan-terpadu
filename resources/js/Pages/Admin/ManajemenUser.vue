<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// ─── Props dari controller ────────────────────────────────────
const props = defineProps({
    users:  { type: Array,  default: () => [] },
    stats:  { type: Object, default: () => ({ total: 0, aktif: 0, nonaktif: 0 }) },
    search: { type: String, default: '' },
});

// ─── State modal ─────────────────────────────────────────────
const showAddModal    = ref(false);
const showEditModal   = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser    = ref(null);

// ─── Search (debounced via Inertia) ──────────────────────────
const searchQuery = ref(props.search);
let searchTimer = null;
watch(searchQuery, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(route('admin.users.index'), { search: val }, {
            preserveState: true,
            replace: true,
        });
    }, 350);
});

// ─── Flash message ───────────────────────────────────────────
const flash = ref('');
const flashType = ref('success'); // 'success' | 'error'
function showFlash(msg, type = 'success') {
    flash.value = msg;
    flashType.value = type;
    setTimeout(() => { flash.value = ''; }, 3500);
}

// ─── Avatar helpers ───────────────────────────────────────────
const avatarColors = [
    'bg-rose-500', 'bg-pink-500', 'bg-violet-500', 'bg-blue-500',
    'bg-cyan-500',  'bg-teal-500',  'bg-green-500',  'bg-amber-500',
    'bg-orange-500','bg-indigo-500',
];
function getInitials(name) {
    const parts = (name ?? '').trim().split(' ');
    if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
    return (parts[0]?.[0] ?? '?').toUpperCase();
}
function getAvatarColor(name) {
    const idx = (name?.charCodeAt(0) ?? 0) % avatarColors.length;
    return avatarColors[idx];
}

// ─── Add Form ─────────────────────────────────────────────────
const addForm = useForm({
    name:   '',
    email:  '',
    status: 'aktif',
});
function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    showAddModal.value = true;
}
function submitAdd() {
    addForm.post(route('admin.users.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddModal.value = false;
            showFlash('Tester berhasil ditambahkan.');
        },
    });
}

// ─── Edit Form ────────────────────────────────────────────────
const editForm = useForm({
    name:   '',
    email:  '',
    status: 'aktif',
});
function openEditModal(user) {
    selectedUser.value = user;
    editForm.name   = user.name;
    editForm.email  = user.email;
    editForm.status = user.status ?? 'aktif';
    editForm.clearErrors();
    showEditModal.value = true;
}
function submitEdit() {
    editForm.put(route('admin.users.update', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            showFlash('Data tester berhasil diperbarui.');
        },
    });
}

// ─── Detail Modal ─────────────────────────────────────────────
function openDetailModal(user) {
    selectedUser.value = user;
    showDetailModal.value = true;
}

// ─── Delete ───────────────────────────────────────────────────
function openDeleteModal(user) {
    selectedUser.value = user;
    showDeleteModal.value = true;
}
function confirmDelete() {
    router.delete(route('admin.users.destroy', selectedUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            showFlash('Tester berhasil dihapus.');
        },
    });
}
</script>

<template>
    <Head title="Manajemen User - MAJADIGI CHECKER" />
    <AdminLayout>

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
                v-if="flash"
                :class="[
                    'fixed top-5 right-5 z-[100] flex items-center gap-2.5 px-4 py-3 rounded-xl shadow-lg text-sm font-semibold',
                    flashType === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700' : 'bg-red-50 border border-red-200 text-red-700'
                ]"
            >
                <svg v-if="flashType === 'success'" class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <svg v-else class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                {{ flash }}
            </div>
        </Transition>

        <!-- ── Page Header ────────────────────────────────────── -->
        <div class="mb-6">
            <h1 class="text-xl font-bold text-slate-800">Manajemen User</h1>
            <p class="text-sm text-slate-500 mt-0.5">Kelola akun tester yang terdaftar</p>
        </div>

        <!-- ── Stats Cards ───────────────────────────────────── -->
        <div class="grid grid-cols-3 gap-4 mb-6">
            <!-- Total -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Tester</p>
                </div>
                <span class="text-3xl font-extrabold text-slate-800">{{ stats.total }}</span>
            </div>
            <!-- Aktif -->
            <div class="bg-emerald-50 rounded-2xl border border-emerald-100 shadow-sm px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Aktif</p>
                </div>
                <span class="text-3xl font-extrabold text-emerald-700">{{ stats.aktif }}</span>
            </div>
            <!-- Nonaktif -->
            <div class="bg-slate-50 rounded-2xl border border-slate-200 shadow-sm px-6 py-4 flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Nonaktif</p>
                </div>
                <span class="text-3xl font-extrabold text-slate-600">{{ stats.nonaktif }}</span>
            </div>
        </div>

        <!-- ── Table Card ────────────────────────────────────── -->
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            <!-- Toolbar -->
            <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                <!-- Search -->
                <div class="relative">
                    <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                    </svg>
                    <input
                        id="search-tester"
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari tester..."
                        class="pl-10 pr-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all w-64"
                    />
                </div>
                <!-- Add Button -->
                <button
                    id="btn-tambah-tester"
                    @click="openAddModal"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
                    </svg>
                    Tambah Tester
                </button>
            </div>

            <!-- Table -->
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-100 bg-slate-50/50">
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Tester</th>
                        <th class="text-left px-5 py-3 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-if="users.length === 0">
                        <td colspan="3" class="text-center py-16 text-slate-400 text-sm">
                            <svg class="mx-auto w-10 h-10 text-slate-300 mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                            </svg>
                            Belum ada tester yang terdaftar
                        </td>
                    </tr>
                    <tr
                        v-for="user in users"
                        :key="user.id"
                        class="hover:bg-slate-50/70 transition-colors group"
                    >
                        <!-- Tester col -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm', getAvatarColor(user.name)]">
                                    {{ getInitials(user.name) }}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 leading-tight">{{ user.name }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ user.email }}</p>
                                </div>
                            </div>
                        </td>
                        <!-- Status col -->
                        <td class="px-5 py-3.5">
                            <span
                                :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    user.status === 'aktif'
                                        ? 'bg-emerald-50 border border-emerald-100 text-emerald-700'
                                        : 'bg-slate-100 border border-slate-200 text-slate-500'
                                ]"
                            >
                                <span :class="['w-1.5 h-1.5 rounded-full', user.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                {{ user.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <!-- Actions col -->
                        <td class="px-5 py-3.5">
                            <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <!-- Detail -->
                                <button
                                    @click="openDetailModal(user)"
                                    title="Lihat detail"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                                <!-- Edit -->
                                <button
                                    @click="openEditModal(user)"
                                    title="Edit data"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-400 hover:text-amber-600 hover:bg-amber-50 transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/>
                                    </svg>
                                </button>
                                <!-- Hapus -->
                                <button
                                    @click="openDeleteModal(user)"
                                    title="Hapus"
                                    class="w-8 h-8 flex items-center justify-center rounded-lg text-rose-400 hover:text-rose-600 hover:bg-rose-50 transition-all"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- Modal: Tambah Tester                                 -->
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
                <div v-if="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showAddModal = false"></div>

                    <!-- Modal Box -->
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                        <!-- Header -->
                        <div class="flex items-start justify-between mb-5">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Tambah Tester Baru</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Lengkapi informasi tester di bawah ini</p>
                            </div>
                            <button @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitAdd" class="space-y-4">
                            <!-- Nama -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input v-model="addForm.name" type="text" placeholder="Nama lengkap"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all"/>
                                <p v-if="addForm.errors.name" class="mt-1 text-xs text-rose-500">{{ addForm.errors.name }}</p>
                            </div>
                            <!-- Email -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input v-model="addForm.email" type="email" placeholder="email@kominfo.jatim.go.id"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all"/>
                                <p v-if="addForm.errors.email" class="mt-1 text-xs text-rose-500">{{ addForm.errors.email }}</p>
                            </div>

                            <!-- Status -->
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label :class="[
                                        'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                        addForm.status === 'aktif' ? 'border-blue-600 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'
                                    ]">
                                        <input type="radio" v-model="addForm.status" value="aktif" class="accent-blue-600"/>
                                        <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    </label>
                                    <label :class="[
                                        'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                        addForm.status === 'tidak aktif' ? 'border-blue-600 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'
                                    ]">
                                        <input type="radio" v-model="addForm.status" value="tidak aktif" class="accent-blue-600"/>
                                        <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Nonaktif
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <!-- Actions -->
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showAddModal = false"
                                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                                    Batal
                                </button>
                                <button type="submit" :disabled="addForm.processing"
                                    class="px-5 py-2.5 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-sm font-semibold shadow-sm transition-all hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Tambah Tester
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- Modal: Edit Data Tester                              -->
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
                <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showEditModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
                        <div class="flex items-start justify-between mb-5">
                            <div>
                                <h3 class="text-base font-bold text-slate-800">Edit Data Tester</h3>
                                <p class="text-xs text-slate-500 mt-0.5">Lengkapi informasi tester di bawah ini</p>
                            </div>
                            <button @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <form @submit.prevent="submitEdit" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Nama Lengkap <span class="text-rose-500">*</span>
                                </label>
                                <input v-model="editForm.name" type="text"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all"/>
                                <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-500">{{ editForm.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                    Email <span class="text-rose-500">*</span>
                                </label>
                                <input v-model="editForm.email" type="email"
                                    class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all"/>
                                <p v-if="editForm.errors.email" class="mt-1 text-xs text-rose-500">{{ editForm.errors.email }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label :class="[
                                        'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                        editForm.status === 'aktif' ? 'border-blue-600 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'
                                    ]">
                                        <input type="radio" v-model="editForm.status" value="aktif" class="accent-blue-600"/>
                                        <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                        </span>
                                    </label>
                                    <label :class="[
                                        'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                        editForm.status === 'tidak aktif' ? 'border-blue-600 bg-blue-50' : 'border-slate-200 bg-white hover:border-slate-300'
                                    ]">
                                        <input type="radio" v-model="editForm.status" value="tidak aktif" class="accent-blue-600"/>
                                        <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                            <span class="w-2 h-2 rounded-full bg-slate-400"></span> Nonaktif
                                        </span>
                                    </label>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-1">
                                <button type="button" @click="showEditModal = false"
                                    class="px-5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                                    Batal
                                </button>
                                <button type="submit" :disabled="editForm.processing"
                                    class="px-5 py-2.5 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-sm font-semibold shadow-sm transition-all hover:-translate-y-0.5 disabled:opacity-50 disabled:cursor-not-allowed">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- Modal: Detail Tester                                 -->
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
                <div v-if="showDetailModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDetailModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                        <div class="flex items-center justify-between mb-5">
                            <h3 class="text-base font-bold text-slate-800">Detail Tester</h3>
                            <button @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>

                        <!-- Profile card -->
                        <div class="bg-blue-50/60 rounded-xl border border-blue-100 p-4 flex items-center gap-3.5 mb-5">
                            <div :class="['w-12 h-12 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-sm', getAvatarColor(selectedUser.name)]">
                                {{ getInitials(selectedUser.name) }}
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 text-sm">{{ selectedUser.name }}</p>
                                <p class="text-xs text-slate-500">{{ selectedUser.email }}</p>
                                <span :class="[
                                    'inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-md text-[11px] font-semibold',
                                    selectedUser.status === 'aktif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'
                                ]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', selectedUser.status === 'aktif' ? 'bg-emerald-500' : 'bg-slate-400']"></span>
                                    {{ selectedUser.status === 'aktif' ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </div>
                        </div>

                        <!-- Detail rows -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-sm text-slate-500">Nama Lengkap</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedUser.name }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-sm text-slate-500">Email</span>
                                <span class="text-sm font-semibold text-slate-800">{{ selectedUser.email }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2 border-b border-slate-100">
                                <span class="text-sm text-slate-500">Role</span>
                                <span class="text-sm font-semibold text-slate-800 capitalize">{{ selectedUser.role }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-sm text-slate-500">Status Akun</span>
                                <span class="text-sm font-semibold text-slate-800 capitalize">{{ selectedUser.status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ════════════════════════════════════════════════════ -->
        <!-- Modal: Konfirmasi Hapus                              -->
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
                <div v-if="showDeleteModal && selectedUser" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="showDeleteModal = false"></div>
                    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 z-10">
                        <div class="flex flex-col items-center text-center mb-5">
                            <div class="w-14 h-14 rounded-2xl bg-rose-100 flex items-center justify-center mb-4">
                                <svg class="w-7 h-7 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m-7 0a1 1 0 01-1-1V5a1 1 0 011-1h6a1 1 0 011 1v1a1 1 0 01-1 1H9z"/>
                                </svg>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">Hapus Tester?</h3>
                            <p class="text-sm text-slate-500 mt-1.5">
                                Akun <span class="font-semibold text-slate-700">{{ selectedUser.name }}</span> akan dihapus permanen dan tidak dapat dikembalikan.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="showDeleteModal = false"
                                class="flex-1 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 hover:bg-slate-50 transition-all">
                                Batal
                            </button>
                            <button @click="confirmDelete"
                                class="flex-1 py-2.5 rounded-xl bg-rose-500 hover:bg-rose-600 text-white text-sm font-semibold shadow-sm transition-all">
                                Ya, Hapus
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AdminLayout>
</template>
