<script setup>
import { ref, computed, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

// ─── Props dari controller ────────────────────────────────────
const props = defineProps({
 users: { type: Array, default: () => [] },
 stats: { type: Object, default: () => ({ total: 0, aktif: 0, nonaktif: 0 }) },
 search: { type: String, default: '' },
});

// ─── State modal ─────────────────────────────────────────────
const showAddModal = ref(false);
const showEditModal = ref(false);
const showDetailModal = ref(false);
const showDeleteModal = ref(false);
const selectedUser = ref(null);

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
let flashTimer = null;
const progressWidth = ref(100);
const flashDuration = ref(4000);

function closeFlash() {
 if (flashTimer) clearTimeout(flashTimer);
 flash.value = '';
}

function showFlash(msg, type = 'success', duration = 4000) {
 if (flashTimer) clearTimeout(flashTimer);
 flash.value = msg;
 flashType.value = type;
 flashDuration.value = duration;
 
 progressWidth.value = 100;
 setTimeout(() => {
 if (flash.value === msg) {
 progressWidth.value = 0;
 }
 }, 50);

 flashTimer = setTimeout(() => { flash.value = ''; }, duration);
}

// ─── Avatar helpers ───────────────────────────────────────────
const avatarColors = [
 'bg-slate-500', 'bg-slate-600', 'bg-slate-700', 'bg-primary',
 'bg-primary', 'bg-primary', 'bg-slate-400',
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
const showAddPassword        = ref(false);
const showAddConfirmPassword = ref(false);

const addForm = useForm({
 name: '',
 email: '',
 password: '',
 password_confirmation: '',
 status: 'aktif',
});
function openAddModal() {
    addForm.reset();
    addForm.clearErrors();
    showAddPassword.value = false;
    showAddConfirmPassword.value = false;
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
 name: '',
 email: '',
 status: 'aktif',
});
function openEditModal(user) {
 selectedUser.value = user;
 editForm.name = user.name;
 editForm.email = user.email;
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
 <AdminLayout
 title="Manajemen User"
 subtitle="Kelola akun tester yang terdaftar"
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
 v-if="flash"
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
 <span>{{ flash }}</span>
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

 <!-- ── Hero Banner ────────────────────────────────────── -->
    <div class="relative rounded-2xl overflow-hidden bg-primary p-6 sm:p-8 shadow-lg mb-6">
      <div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_3.jpg');"></div>
      <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>
      <div class="relative z-10">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-white/15 border border-white/20 mb-2 text-[11px] font-bold tracking-wider uppercase text-cyan-100 backdrop-blur-sm">
          <span class="w-2 h-2 rounded-full bg-cyan-300 animate-pulse"></span>
          Pengguna Sistem
        </div>
        <h2 class="text-xl sm:text-2xl font-bold text-white tracking-tight">Manajemen User & Tester</h2>
        <p class="text-white/80 text-xs sm:text-sm mt-1 max-w-xl">Kelola data pengguna, hak akses tester, NIP, serta bidang dinas dalam sistem MAJADIGI.</p>
      </div>
    </div>

    <!-- ── Stats Cards ───────────────────────────────────── -->
 <div class="grid grid-cols-3 gap-2.5 sm:gap-4 mb-6">
 <!-- Total -->
 <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:px-6 sm:py-5 flex flex-col gap-2">
 <span class="text-[11px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">TOTAL TESTER</span>
 <span class="text-2xl sm:text-3xl font-black text-slate-800">{{ stats.total }}</span>
 </div>
 <!-- Aktif -->
 <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:px-6 sm:py-5 flex flex-col gap-2">
 <span class="text-[11px] sm:text-xs font-bold text-primary uppercase tracking-wider">AKTIF</span>
 <span class="text-2xl sm:text-3xl font-black text-slate-800">{{ stats.aktif }}</span>
 </div>
 <!-- Nonaktif -->
 <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:px-6 sm:py-5 flex flex-col gap-2">
 <span class="text-[11px] sm:text-xs font-bold text-slate-500 uppercase tracking-wider">NONAKTIF</span>
 <span class="text-2xl sm:text-3xl font-black text-slate-800">{{ stats.nonaktif }}</span>
 </div>
 </div>

 <!-- ── Table Card ────────────────────────────────────── -->
 <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

 <!-- Toolbar -->
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 sm:px-6 sm:py-4 border-b border-slate-100">
 <!-- Search -->
 <div class="relative w-full sm:w-72">
 <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
 </svg>
 <input
 id="search-tester"
 v-model="searchQuery"
 type="text"
 placeholder="Cari tester..."
 class="w-full pl-10 pr-4 py-2.5 sm:py-2 rounded-xl border border-slate-200 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"
 />
 </div>
 <!-- Add Button -->
 <button
 id="btn-tambah-tester"
 @click="openAddModal"
 class="w-full sm:w-auto flex items-center justify-center gap-2 px-4 py-2.5 sm:py-2 rounded-xl bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white text-sm font-semibold shadow-sm transition-all duration-200 hover:-translate-y-0.5 shrink-0 active:scale-[0.99]"
 >
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14"/>
 </svg>
 <span>Tambah Tester</span>
 </button>
 </div>

 <!-- Table Container with Horizontal Scroll -->
 <div class="overflow-x-auto">
 <table class="w-full text-sm min-w-[500px] sm:min-w-full">
 <thead>
 <tr class="border-b border-slate-100 bg-slate-50/60">
 <th class="text-left px-4 sm:px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider">Tester</th>
 <th class="text-left px-3 sm:px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider w-36 sm:w-44">Status</th>
 <th class="text-right px-4 sm:px-6 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wider w-32">Aksi</th>
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
 <td class="px-4 sm:px-6 py-4 text-left">
 <div class="flex items-center gap-3">
 <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-white text-xs font-bold shrink-0 shadow-sm', getAvatarColor(user.name)]">
 {{ getInitials(user.name) }}
 </div>
 <div class="min-w-0">
 <p class="font-semibold text-slate-800 leading-tight truncate">{{ user.name }}</p>
 <p class="text-xs text-slate-400 mt-0.5 truncate">{{ user.email }}</p>
 </div>
 </div>
 </td>
 <!-- Status col -->
 <td class="px-3 sm:px-6 py-4 text-left w-36 sm:w-44">
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
 <td class="px-4 sm:px-6 py-4 text-right w-32">
 <div class="flex items-center justify-end gap-1.5">
 <!-- Detail / Read -->
 <button
 @click="openDetailModal(user)"
 title="Lihat detail"
 aria-label="Lihat detail"
 class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-primary hover:bg-slate-100 active:scale-95 transition-all"
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
 aria-label="Edit data"
 class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-amber-600 hover:bg-amber-50 active:scale-95 transition-all"
 >
 <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
 </svg>
 </button>
 <!-- Hapus -->
 <button
 @click="openDeleteModal(user)"
 title="Hapus"
 aria-label="Hapus user"
 class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-rose-600 hover:bg-rose-50 active:scale-95 transition-all"
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
 </div>

 <!-- ════════════════════════════════════════════════════ -->
 <!-- Modal: Tambah Tester -->
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
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"/>
                                    <p v-if="addForm.errors.name" class="mt-1 text-xs text-rose-500">{{ addForm.errors.name }}</p>
                                </div>
                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Email <span class="text-rose-500">*</span>
                                    </label>
                                    <input v-model="addForm.email" type="email" placeholder="email@kominfo.jatim.go.id"
                                        class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"/>
                                    <p v-if="addForm.errors.email" class="mt-1 text-xs text-rose-500">{{ addForm.errors.email }}</p>
                                </div>

                                <!-- Password -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Password <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="addForm.password"
                                            :type="showAddPassword ? 'text' : 'password'"
                                            placeholder="Masukkan password"
                                            class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"
                                        />
                                        <button
                                            type="button"
                                            @click="showAddPassword = !showAddPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                                            :title="showAddPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                        >
                                            <svg v-if="!showAddPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="addForm.errors.password" class="mt-1 text-xs text-rose-500">{{ addForm.errors.password }}</p>
                                </div>

                                <!-- Konfirmasi Password -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                        Konfirmasi Password <span class="text-rose-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <input
                                            v-model="addForm.password_confirmation"
                                            :type="showAddConfirmPassword ? 'text' : 'password'"
                                            placeholder="Ulangi password"
                                            class="w-full pl-3.5 pr-10 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 placeholder-slate-400 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"
                                        />
                                        <button
                                            type="button"
                                            @click="showAddConfirmPassword = !showAddConfirmPassword"
                                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none p-1"
                                            :title="showAddConfirmPassword ? 'Sembunyikan password' : 'Tampilkan password'"
                                        >
                                            <svg v-if="!showAddConfirmPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <p v-if="addForm.errors.password_confirmation" class="mt-1 text-xs text-rose-500">{{ addForm.errors.password_confirmation }}</p>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
                                    <div class="grid grid-cols-2 gap-2">
                                        <label :class="[
                                            'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                            addForm.status === 'aktif' ? 'border-primary bg-primary/10' : 'border-slate-200 bg-white hover:border-slate-300'
                                        ]">
                                            <input type="radio" v-model="addForm.status" value="aktif" class="accent-primary"/>
                                            <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
                                            </span>
                                        </label>
                                        <label :class="[
                                            'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
                                            addForm.status === 'tidak aktif' ? 'border-primary bg-primary/10' : 'border-slate-200 bg-white hover:border-slate-300'
                                        ]">
                                            <input type="radio" v-model="addForm.status" value="tidak aktif" class="accent-primary"/>
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
 <!-- Modal: Edit Data Tester -->
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
 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"/>
 <p v-if="editForm.errors.name" class="mt-1 text-xs text-rose-500">{{ editForm.errors.name }}</p>
 </div>
 <div>
 <label class="block text-sm font-semibold text-slate-700 mb-1.5">
 Email <span class="text-rose-500">*</span>
 </label>
 <input v-model="editForm.email" type="email"
 class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm text-slate-800 focus:outline-none focus:border-primary focus:ring-3 focus:ring-primary/10 transition-all"/>
 <p v-if="editForm.errors.email" class="mt-1 text-xs text-rose-500">{{ editForm.errors.email }}</p>
 </div>

 <div>
 <label class="block text-sm font-semibold text-slate-700 mb-2">Status Akun</label>
 <div class="grid grid-cols-2 gap-2">
 <label :class="[
 'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
 editForm.status === 'aktif' ? 'border-primary bg-primary/10' : 'border-slate-200 bg-white hover:border-slate-300'
 ]">
 <input type="radio" v-model="editForm.status" value="aktif" class="accent-primary"/>
 <span class="flex items-center gap-1.5 text-sm font-semibold text-slate-700">
 <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Aktif
 </span>
 </label>
 <label :class="[
 'flex items-center gap-2.5 px-4 py-2.5 rounded-xl border-2 cursor-pointer transition-all',
 editForm.status === 'tidak aktif' ? 'border-primary bg-primary/10' : 'border-slate-200 bg-white hover:border-slate-300'
 ]">
 <input type="radio" v-model="editForm.status" value="tidak aktif" class="accent-primary"/>
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
 <!-- Modal: Detail Tester -->
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
 <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 flex items-center gap-3.5 mb-5">
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
 <!-- Modal: Konfirmasi Hapus -->
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
