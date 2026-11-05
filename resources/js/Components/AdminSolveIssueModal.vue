<script setup>
defineProps({
    show: { type: Boolean, default: false },
    item: { type: Object, default: null },
    form: { type: Object, required: true },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="show && item" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg my-8 overflow-hidden z-10 border border-slate-100">
                    <!-- Header Modal -->
                    <div class="relative bg-gradient-to-r from-emerald-600 to-teal-700 p-6 text-white overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>

                        <div class="relative flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 shadow-inner shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="inline-block text-[10px] font-bold uppercase tracking-wider text-emerald-100 bg-white/15 px-2.5 py-0.5 rounded-full mb-1">
                                        Tindak Lanjut Kendala
                                    </span>
                                    <h3 class="text-lg font-bold text-white leading-tight">Tandai Selesai Kendala</h3>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="emit('close')"
                                class="w-8 h-8 rounded-full bg-white/20 hover:bg-white/30 text-white flex items-center justify-center transition-colors cursor-pointer shrink-0"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Body Form -->
                    <form @submit.prevent="emit('submit')">
                        <div class="p-6 space-y-4 max-h-[calc(100vh-230px)] overflow-y-auto">
                            <!-- Info Layanan Singkat -->
                            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80">
                                <div class="flex items-center gap-2 mb-1">
                                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">
                                        {{ item.category }}
                                    </span>
                                    <span v-if="item.level && item.level !== '-'" class="text-[10px] font-semibold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-600">
                                        Level {{ item.level }}
                                    </span>
                                </div>
                                <h4 class="text-sm font-bold text-slate-800">{{ item.service_name }}</h4>
                                <p class="text-[11px] text-slate-400 mt-1">
                                    Diperiksa oleh <span class="font-semibold text-slate-600">{{ item.tester_name }}</span> <span class="mx-1 text-slate-300">|</span> {{ item.checked_at }}
                                </p>
                            </div>

                            <!-- 1. Catatan Sebelumnya dari Tester -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                    </svg>
                                    Catatan Kendala Sebelumnya (Tester):
                                </label>
                                <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-200/80 text-slate-700 text-xs leading-relaxed font-medium whitespace-pre-wrap max-h-32 overflow-y-auto">
                                    {{ item.description || 'Tidak ada catatan kendala sebelumnya.' }}
                                </div>
                            </div>

                            <!-- 2. Textarea Pre-filled untuk Edit / Pembaruan Catatan Penanganan Admin -->
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Catatan Penanganan Admin:
                                    </label>
                                    <span class="text-[10px] text-slate-400 font-medium">Pre-filled (Dapat Diedit)</span>
                                </div>
                                <p class="text-[11px] text-slate-400 mb-2 leading-tight">
                                    Perbarui catatan penanganan sesuai kondisi terbaru layanan (misal: kendala sudah diperbaiki oleh tim terkait). Status penanganan akan menjadi <strong>Perlu Pengecekan Ulang</strong> untuk diperiksa oleh Tester.
                                </p>

                                <div class="relative">
                                    <textarea
                                        v-model="form.description"
                                        rows="4"
                                        placeholder="Contoh: Kendala sudah diperbaiki oleh tim terkait dan layanan sudah kembali normal diakses."
                                        class="w-full rounded-2xl border border-slate-300 p-3.5 text-xs text-slate-800 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-600 transition-all leading-relaxed"
                                        :class="{'border-rose-400 focus:ring-rose-500/20 focus:border-rose-500': form.errors}"
                                    ></textarea>
                                </div>

                                <div class="flex items-center justify-between mt-1">
                                    <p v-if="form.errors" class="text-[11px] text-rose-600 font-semibold flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        {{ form.errors }}
                                    </p>
                                    <span v-else></span>
                                    <span class="text-[10px] text-slate-400 font-medium ml-auto">
                                        {{ (form.description || '').length }} / 5000 karakter
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Form Modal -->
                        <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="emit('close')"
                                :disabled="form.processing"
                                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer disabled:opacity-50"
                            >
                                Batal
                            </button>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold inline-flex items-center gap-2 shadow-md shadow-emerald-600/20 active:scale-95 transition-all cursor-pointer disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                                {{ form.processing ? 'Menyimpan...' : 'Simpan & Tandai Selesai' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

