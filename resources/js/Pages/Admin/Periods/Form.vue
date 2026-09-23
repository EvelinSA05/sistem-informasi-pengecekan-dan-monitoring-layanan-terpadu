<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';

const props = defineProps({
    isEdit:           { type: Boolean, default: false },
    period:           { type: Object,  default: null },
    testers:          { type: Array,   default: () => [] },
    services:         { type: Array,   default: () => [] },
    initialDate:      { type: String,  default: '' },
    initialTesterIds: { type: Array,   default: () => [] },
});

// ─── Form State ──────────────────────────────────────────────────
const form = useForm({
    date: props.initialDate || props.period?.date || new Date().toISOString().split('T')[0],
    deadline_date: props.period?.deadline_date || '',
    tester_ids: [...props.initialTesterIds],
});

// ─── Toggle Tester Selection ─────────────────────────────────────
const availableTesters = computed(() => props.testers.filter(t => t.is_available !== false));
const allAvailableSelected = computed(() => {
    return availableTesters.value.length > 0 && availableTesters.value.every(t => form.tester_ids.includes(t.id));
});

function toggleSelectAllAvailable() {
    if (allAvailableSelected.value) {
        form.tester_ids = [];
    } else {
        form.tester_ids = availableTesters.value.map(t => t.id);
    }
}

function toggleTester(tester) {
    if (tester.is_available === false && !isTesterSelected(tester.id)) {
        return;
    }
    const idx = form.tester_ids.indexOf(tester.id);
    if (idx > -1) {
        form.tester_ids.splice(idx, 1);
    } else {
        form.tester_ids.push(tester.id);
    }
}

function isTesterSelected(testerId) {
    return form.tester_ids.includes(testerId);
}

// ─── Avatar Color Helper ─────────────────────────────────────────
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

// ─── Service Icon Helper ─────────────────────────────────────────
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

// ─── Reactive Automatic Distribution Calculation ─────────────────
const selectedTestersCount = computed(() => {
    return props.testers.filter(t => form.tester_ids.includes(t.id)).length;
});

const perTesterEstimate = computed(() => {
    if (selectedTestersCount.value === 0) return 0;
    return Math.round(props.services.length / selectedTestersCount.value);
});

const distributedGroups = computed(() => {
    if (selectedTestersCount.value === 0) return [];

    const selectedTestersList = props.testers.filter(t => form.tester_ids.includes(t.id));
    const groups = selectedTestersList.map(tester => ({
        tester,
        services: [],
    }));

    props.services.forEach((service, index) => {
        const groupIndex = index % selectedTestersCount.value;
        groups[groupIndex].services.push(service);
    });

    return groups;
});

// ─── Submit Form ─────────────────────────────────────────────────
function submitForm() {
    if (props.isEdit) {
        form.put(route('admin.periods.update', props.period.id), {
            preserveScroll: true,
        });
    } else {
        form.post(route('admin.periods.store'), {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head :title="`${isEdit ? 'Edit Periode' : 'Buat Periode Baru'} - MAJADIGI CHECKER`" />

    <AdminLayout
        title="Periode Pengecekan"
        subtitle="Atur periode dan distribusi layanan"
    >
        <!-- ── Top Header Navigation (Image 4 & Image 5) ───────── -->
        <div class="flex items-center gap-4 mb-6">
            <Link
                :href="route('admin.periods.index')"
                class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 hover:text-blue-700 transition-colors group"
            >
                <span class="text-sm transition-transform group-hover:-translate-x-0.5">←</span>
                <span>Kembali</span>
            </Link>
            <h2 class="text-xl font-bold text-slate-900 tracking-tight">
                {{ isEdit ? 'Edit Periode' : 'Buat Periode Baru' }}
            </h2>
        </div>

        <!-- ── Main Grid Layout ────────────────────────────────── -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ── Left Column: Form Periode & Tester ───────────── -->
            <div :class="[selectedTestersCount > 0 ? 'lg:col-span-6' : 'lg:col-span-7 max-w-2xl']">
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-7 space-y-6">

                    <!-- Section: INFORMASI PERIODE -->
                    <div>
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">
                            INFORMASI PERIODE
                        </h3>
                        <div>
                            <label for="tanggal_pengecekan" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tanggal Pengecekan <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <input
                                    id="tanggal_pengecekan"
                                    v-model="form.date"
                                    type="date"
                                    required
                                    class="w-full px-4 py-2.5 bg-white rounded-2xl border text-sm font-semibold text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all shadow-2xs cursor-pointer text-slate-700"
                                    :class="form.errors.date
                                        ? 'border-rose-500 ring-2 ring-rose-500/10'
                                        : 'border-slate-200 hover:border-slate-300'"
                                />
                            </div>
                            <p v-if="form.errors.date" class="text-xs text-rose-500 mt-1 font-medium">
                                {{ form.errors.date }}
                            </p>
                        </div>

                        <div class="mt-4">
                            <label for="deadline_date" class="block text-xs font-semibold text-slate-700 mb-1.5">
                                Tenggat Waktu (Opsional)
                            </label>
                            <div class="relative">
                                <input
                                    id="deadline_date"
                                    v-model="form.deadline_date"
                                    type="date"
                                    class="w-full px-4 py-2.5 bg-white rounded-2xl border text-sm font-semibold text-slate-800 focus:outline-none focus:border-blue-500 focus:ring-3 focus:ring-blue-500/10 transition-all shadow-2xs cursor-pointer text-slate-700"
                                    :class="form.errors.deadline_date
                                        ? 'border-rose-500 ring-2 ring-rose-500/10'
                                        : 'border-slate-200 hover:border-slate-300'"
                                />
                            </div>
                            <p v-if="form.errors.deadline_date" class="text-xs text-rose-500 mt-1 font-medium">
                                {{ form.errors.deadline_date }}
                            </p>
                        </div>
                    </div>

                    <!-- Section: PILIH TESTER -->
                    <div>
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2.5">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">
                                    PILIH TESTER <span class="text-rose-500">*</span>
                                </h3>
                                <button
                                    v-if="availableTesters.length > 0"
                                    type="button"
                                    @click="toggleSelectAllAvailable"
                                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 transition-colors cursor-pointer underline underline-offset-2"
                                >
                                    {{ allAvailableSelected ? 'Batal Pilih Semua' : 'Pilih Semua Tersedia' }}
                                </button>
                            </div>
                            <span v-if="selectedTestersCount > 0" class="text-xs font-semibold text-blue-600">
                                {{ selectedTestersCount }} dipilih
                            </span>
                        </div>

                        <!-- List of tester cards -->
                        <div class="space-y-2.5">
                            <div
                                v-for="tester in testers"
                                :key="tester.id"
                                @click="toggleTester(tester)"
                                :class="[
                                    'select-none rounded-2xl p-4 border transition-all duration-150 flex items-center justify-between',
                                    tester.is_available === false && !isTesterSelected(tester.id)
                                        ? 'bg-slate-50/70 border-slate-200/70 opacity-60 cursor-not-allowed'
                                        : (isTesterSelected(tester.id)
                                            ? 'cursor-pointer bg-blue-50/30 border-blue-500 ring-1 ring-blue-500/20 shadow-xs'
                                            : 'cursor-pointer bg-white border-slate-200/80 hover:border-slate-300 hover:bg-slate-50/50')
                                ]"
                            >
                                <div class="flex items-center gap-3.5">
                                    <!-- Checkbox circle / Lock icon -->
                                    <div
                                        :class="[
                                            'w-5 h-5 rounded-full flex items-center justify-center transition-colors',
                                            isTesterSelected(tester.id)
                                                ? 'bg-[#1e3a8a] text-white'
                                                : (tester.is_available === false ? 'border-2 border-slate-200 bg-slate-100 text-slate-400' : 'border-2 border-slate-300 bg-white')
                                        ]"
                                    >
                                        <svg
                                            v-if="isTesterSelected(tester.id)"
                                            class="w-3 h-3 stroke-[3]"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        <svg
                                            v-else-if="tester.is_available === false"
                                            class="w-3 h-3 text-slate-400 stroke-2"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                        </svg>
                                    </div>

                                    <!-- Initials Avatar -->
                                    <div
                                        :class="[
                                            'w-9 h-9 rounded-full flex items-center justify-center text-white text-xs font-bold shadow-sm',
                                            getTesterAvatarColor(tester.initials, tester.name)
                                        ]"
                                    >
                                        {{ tester.initials }}
                                    </div>

                                    <!-- Name & Email -->
                                    <div>
                                        <p class="text-sm font-bold text-slate-800 leading-tight">
                                            {{ tester.name }}
                                        </p>
                                        <p class="text-xs text-slate-400 mt-0.5 font-medium">
                                            {{ tester.email }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Right side badge -->
                                <div v-if="isTesterSelected(tester.id)">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg border border-slate-200/90 bg-white text-[11px] font-semibold text-slate-600 shadow-2xs">
                                        ~{{ perTesterEstimate }} layanan
                                    </span>
                                </div>
                                <div v-else-if="tester.is_available === false">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-amber-200/80 bg-amber-50 text-[11px] font-semibold text-amber-700 shadow-2xs">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                        {{ tester.pending_tasks_count }} pending
                                    </span>
                                </div>
                            </div>
                        </div>

                        <p v-if="form.errors.tester_ids" class="text-xs text-rose-500 mt-2 font-medium">
                            {{ form.errors.tester_ids }}
                        </p>
                    </div>

                    <!-- Notice banner for Automatic Distribution (matching Image 4 & Image 5) -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="selectedTestersCount > 0"
                            class="bg-blue-50/70 border border-blue-200/80 rounded-2xl p-4 flex items-center gap-3 text-xs text-blue-900"
                        >
                            <div class="w-8 h-8 rounded-xl bg-blue-100/80 text-blue-700 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                            </div>
                            <p class="leading-relaxed">
                                <strong class="font-bold">Distribusi Otomatis:</strong> {{ services.length }} layanan dibagi ke {{ selectedTestersCount }} tester secara merata
                            </p>
                        </div>
                    </Transition>

                    <!-- Action Buttons (Image 5: "Batal" & "Simpan" for Edit; "Buat & Distribusikan" for Create) -->
                    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                        <Link
                            :href="route('admin.periods.index')"
                            class="px-5 py-2.5 rounded-2xl border border-slate-200 text-xs font-bold text-slate-600 hover:bg-slate-50 transition-colors"
                        >
                            Batal
                        </Link>
                        <button
                            type="button"
                            @click="submitForm"
                            :disabled="selectedTestersCount === 0 || form.processing"
                            :class="[
                                'px-6 py-2.5 rounded-2xl text-xs font-bold transition-all duration-200 shadow-md',
                                selectedTestersCount === 0 || form.processing
                                    ? 'bg-blue-400 text-white/80 cursor-not-allowed shadow-none'
                                    : 'bg-[#1e3a8a] hover:bg-[#1d4ed8] text-white shadow-blue-900/10 hover:shadow-blue-900/20 hover:-translate-y-0.5'
                            ]"
                        >
                            <span v-if="form.processing">Memproses...</span>
                            <span v-else-if="isEdit">Simpan</span>
                            <span v-else>Buat & Distribusikan</span>
                        </button>
                    </div>

                </div>
            </div>

            <!-- ── Right Column: Preview Distribusi (Image 5) ───── -->
            <div
                v-if="selectedTestersCount > 0"
                class="lg:col-span-6 animate-in fade-in slide-in-from-right-3 duration-300"
            >
                <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm p-6">
                    <!-- Preview Header -->
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100 mb-4">
                        <h3 class="text-sm font-bold text-slate-800">
                            Preview Distribusi
                        </h3>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600">
                            {{ services.length }} layanan
                        </span>
                    </div>

                    <!-- Groups List by Tester -->
                    <div class="space-y-6 max-h-[620px] overflow-y-auto pr-1">
                        <div
                            v-for="group in distributedGroups"
                            :key="group.tester.id"
                            class="space-y-3"
                        >
                            <!-- Tester Header Row -->
                            <div class="flex items-center justify-between bg-slate-50/80 px-3.5 py-2 rounded-xl border border-slate-100">
                                <div class="flex items-center gap-2.5">
                                    <div
                                        :class="[
                                            'w-7 h-7 rounded-full flex items-center justify-center text-white text-[10.5px] font-bold shadow-xs',
                                            getTesterAvatarColor(group.tester.initials, group.tester.name)
                                        ]"
                                    >
                                        {{ group.tester.initials }}
                                    </div>
                                    <p class="text-xs font-bold text-slate-800">
                                        {{ group.tester.name }}
                                    </p>
                                </div>
                                <span class="text-[11px] font-semibold text-slate-500 bg-white px-2.5 py-0.5 rounded-lg border border-slate-200/70 shadow-2xs">
                                    {{ group.services.length }} layanan
                                </span>
                            </div>

                            <!-- List of Services for this Tester -->
                            <div class="pl-3 space-y-2 border-l-2 border-slate-100 ml-3.5">
                                <div
                                    v-for="service in group.services"
                                    :key="service.id"
                                    class="flex items-center gap-3 py-1.5 px-2 rounded-xl hover:bg-slate-50 transition-colors"
                                >
                                    <!-- Service Icon -->
                                    <div class="w-8 h-8 rounded-xl bg-slate-100/90 flex items-center justify-center shrink-0 border border-slate-200/50" v-html="getServiceIcon(service.icon)">
                                    </div>

                                    <!-- Service Name & Category -->
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-bold text-slate-800 truncate leading-tight">
                                            {{ service.service_name }}
                                        </p>
                                        <p class="text-[11px] text-slate-400 mt-0.5 truncate font-medium">
                                            {{ service.category || 'Layanan' }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
