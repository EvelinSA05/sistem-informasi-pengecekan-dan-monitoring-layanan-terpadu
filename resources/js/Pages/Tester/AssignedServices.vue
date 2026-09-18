<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    activePeriod: String,
    assignedServices: Array,
});

const showCheckModal = ref(false);
const selectedService = ref(null);

const checkForm = useForm({
    status: '',
    description: '',
});

const openCheckModal = (service) => {
    selectedService.value = service;
    checkForm.status = service.status === 'normal' || service.status === 'gangguan' ? service.status : '';
    checkForm.description = service.last_description || '';
    checkForm.clearErrors();
    showCheckModal.value = true;
};

const submitCheck = () => {
    checkForm.post(route('tester.submit_check', selectedService.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showCheckModal.value = false;
        },
    });
};

const searchQuery = ref('');
const statusFilter = ref('');

const filteredServices = computed(() => {
    return props.assignedServices.filter(service => {
        const matchesSearch = service.name.toLowerCase().includes(searchQuery.value.toLowerCase()) || 
                              service.url?.toLowerCase().includes(searchQuery.value.toLowerCase());
        const matchesStatus = statusFilter.value ? (
            statusFilter.value === 'checked' ? (service.status === 'normal' || service.status === 'gangguan') : 
            service.status === statusFilter.value
        ) : true;
        
        return matchesSearch && matchesStatus;
    });
});
</script>

<template>
    <Head title="Layanan Ditugaskan" />

    <AdminLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Layanan Ditugaskan</h2>
                    <p class="text-sm text-slate-500 mt-1 font-medium">
                        Periode berjalan: <span class="text-blue-600 font-bold capitalize">{{ activePeriod || 'Belum ada periode aktif' }}</span>
                    </p>
                </div>
            </div>
        </template>

        <div class="max-w-7xl mx-auto space-y-6">
            <!-- Filter & Search -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 flex flex-col sm:flex-row gap-4 justify-between items-center">
                <div class="relative w-full sm:w-96">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <input type="text" v-model="searchQuery" placeholder="Cari layanan berdasarkan nama atau URL..." class="pl-10 w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                </div>
                <div class="w-full sm:w-auto">
                    <select v-model="statusFilter" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500 bg-slate-50">
                        <option value="">Semua Status</option>
                        <option value="checked">Sudah Dicek</option>
                        <option value="pending">Belum Dicek</option>
                    </select>
                </div>
            </div>

            <!-- Bagian Layanan yang Ditugaskan -->
            <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm overflow-hidden">
                <div class="flex items-center justify-between p-6 border-b border-slate-100">
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-800">Daftar Penugasan</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Klik layanan untuk menguji dan melaporkan statusnya</p>
                    </div>
                </div>

                <!-- Empty State bila belum ada penugasan -->
                <div v-if="filteredServices.length === 0" class="py-14 px-4 flex flex-col items-center justify-center text-center">
                    <div class="w-16 h-16 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-sm mb-4">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <h4 class="text-base font-bold text-slate-800">Belum Ada Layanan</h4>
                    <p class="text-sm text-slate-400 max-w-md mt-1 leading-relaxed">
                        Data layanan tidak ditemukan berdasarkan kriteria pencarian Anda, atau admin belum membagikan daftar layanan untuk periode ini.
                    </p>
                </div>

                <!-- List Layanan jika sudah ada penugasan -->
                <div v-else class="divide-y divide-slate-100 p-6 pt-0 mt-4">
                    <button
                        v-for="service in filteredServices"
                        :key="service.id"
                        @click="openCheckModal(service)"
                        class="w-full py-4.5 flex items-center justify-between gap-4 group hover:bg-slate-50/60 rounded-xl px-2 transition-colors duration-150 text-left"
                    >
                        <div class="flex items-center gap-4 min-w-0">
                            <div class="w-11 h-11 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl shrink-0 shadow-sm transition-transform group-hover:scale-105">
                                🏛️
                            </div>
                            <div class="min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate leading-snug group-hover:text-blue-600 transition-colors">{{ service.name }}</h4>
                                <p class="text-xs text-slate-400 truncate mt-0.5">
                                    <span>{{ service.category }}</span>
                                    <span v-if="service.url"> · {{ service.url }}</span>
                                </p>
                            </div>
                        </div>

                        <div class="shrink-0 flex items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                                :class="service.status === 'normal' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : (service.status === 'gangguan' ? 'bg-rose-50 text-rose-600 border border-rose-100' : 'bg-slate-100 text-slate-600')"
                            >
                                <span class="w-1.5 h-1.5 rounded-full" :class="service.status === 'normal' ? 'bg-emerald-400' : (service.status === 'gangguan' ? 'bg-rose-400' : 'bg-slate-400')"></span>
                                {{ service.status === 'normal' ? 'NORMAL' : (service.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
                            </span>
                            <svg class="w-5 h-5 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                    </button>
                </div>
            </div>
        </div>

        <!-- ── Modal Pengecekan Layanan (Tester) ────────────────── -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0 scale-95 translate-y-4"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 translate-y-4"
        >
            <div v-if="showCheckModal && selectedService" class="fixed inset-0 z-[110] flex items-center justify-center p-4 sm:p-6 bg-slate-900/40 backdrop-blur-sm">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-800">Cek Layanan</h3>
                            <p class="text-xs text-slate-500">{{ selectedService.name }}</p>
                        </div>
                        <button @click="showCheckModal = false" class="text-slate-400 hover:text-slate-600 transition-colors p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg></button>
                    </div>

                    <div class="p-6 overflow-y-auto space-y-5">
                        <!-- Informasi Layanan -->
                        <div class="space-y-4 bg-slate-50 p-5 rounded-2xl border border-slate-100">
                            <div>
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">URL Layanan</p>
                                <a v-if="selectedService.url" :href="selectedService.url" target="_blank" class="text-sm font-medium text-blue-600 hover:underline inline-flex items-center gap-1.5">
                                    {{ selectedService.url }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                                <p v-else class="text-sm text-slate-500">-</p>
                            </div>
                            <div v-if="selectedService.deskripsi_layanan">
                                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Deskripsi Layanan</p>
                                <p class="text-sm text-slate-700">{{ selectedService.deskripsi_layanan }}</p>
                            </div>
                            <div v-if="selectedService.tester_instruction" class="bg-amber-50 p-4 rounded-xl border border-amber-100">
                                <p class="text-xs font-bold text-amber-700 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    Instruksi Khusus Tester
                                </p>
                                <p class="text-sm text-amber-900 whitespace-pre-wrap">{{ selectedService.tester_instruction }}</p>
                            </div>
                        </div>

                        <!-- Form Pelaporan -->
                        <form @submit.prevent="submitCheck" class="space-y-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-2">Hasil Pengecekan <span class="text-rose-500">*</span></label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="checkForm.status" value="normal" class="peer sr-only">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center peer-checked:bg-emerald-50 peer-checked:border-emerald-500 peer-checked:text-emerald-700 transition-colors">
                                            <p class="font-bold text-sm">NORMAL</p>
                                            <p class="text-xs mt-0.5 opacity-80">Layanan Berfungsi</p>
                                        </div>
                                    </label>
                                    <label class="relative cursor-pointer">
                                        <input type="radio" v-model="checkForm.status" value="gangguan" class="peer sr-only">
                                        <div class="p-3 rounded-xl border border-slate-200 text-center peer-checked:bg-rose-50 peer-checked:border-rose-500 peer-checked:text-rose-700 transition-colors">
                                            <p class="font-bold text-sm">KENDALA</p>
                                            <p class="text-xs mt-0.5 opacity-80">Ada Error/Gangguan</p>
                                        </div>
                                    </label>
                                </div>
                                <p v-if="checkForm.errors.status" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.status }}</p>
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Catatan / Keterangan Kendala</label>
                                <textarea v-model="checkForm.description" rows="3" class="w-full rounded-xl border-slate-200 text-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Jelaskan secara singkat kendala yang ditemukan, jika ada..."></textarea>
                                <p v-if="checkForm.errors.description" class="mt-1 text-xs text-rose-500">{{ checkForm.errors.description }}</p>
                            </div>
                        </form>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-slate-50 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" @click="showCheckModal = false" class="px-5 py-2.5 rounded-xl font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 transition-colors">Batal</button>
                        <button type="button" @click="submitCheck" :disabled="checkForm.processing" class="px-5 py-2.5 rounded-xl font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all disabled:opacity-70 flex items-center gap-2">
                            <svg v-if="checkForm.processing" class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            Simpan Hasil
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>
