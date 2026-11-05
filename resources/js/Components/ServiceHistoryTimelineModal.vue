<script setup>
import { ref, computed, watch } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    service: { type: Object, default: null },
    periodInfo: { type: Object, default: null },
});

const emit = defineEmits(['close', 'recheck']);

const timelineOrder = ref('asc'); // 'asc' = Kronologis (Awal ke Baru), 'desc' = Terbaru ke Lama

watch(() => props.show, (isOpen) => {
    if (isOpen) {
        timelineOrder.value = 'asc';
    }
});

const historyTimelineList = computed(() => {
    if (!props.service) return [];
    const list = props.service.timeline || props.service.check_logs || [];
    if (timelineOrder.value === 'desc') {
        return [...list].reverse();
    }
    return [...list];
});

function toggleTimelineOrder() {
    timelineOrder.value = timelineOrder.value === 'asc' ? 'desc' : 'asc';
}

const failedImages = ref(new Set());

function onImageError(key) {
    if (key) failedImages.value.add(key);
}

function isImageAttachment(item) {
    if (!item) return false;
    if (typeof item === 'object') {
        if (item.attachment_mime && item.attachment_mime.startsWith('image/')) return true;
        const nameOrUrl = item.attachment_name || item.attachment || item.attachment_url || '';
        if (/\.(jpg|jpeg|png|webp|gif|svg)(\?.*)?$/i.test(nameOrUrl)) return true;
        if (item.attachment_url && !/\.(pdf|doc|docx|xls|xlsx|zip|rar|txt)(\?.*)?$/i.test(nameOrUrl)) return true;
        return false;
    }
    const str = String(item);
    if (/\.(jpg|jpeg|png|webp|gif|svg)(\?.*)?$/i.test(str)) return true;
    if (!/\.(pdf|doc|docx|xls|xlsx|zip|rar|txt)(\?.*)?$/i.test(str) && str.includes('/attachment/')) return true;
    return false;
}

function shouldShowPreview(item) {
    if (!item || !item.attachment_url) return false;
    const key = item.id || item.attachment_url;
    if (failedImages.value.has(key)) return false;
    return isImageAttachment(item);
}
</script>

<template>
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 scale-95"
        enter-to-class="opacity-100 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-95"
    >
        <div
            v-if="show && service"
            class="fixed inset-0 z-[150] flex items-center justify-center p-4 sm:p-6 bg-slate-950/60 backdrop-blur-xs overflow-y-auto"
            @click.self="emit('close')"
        >
            <div class="bg-white rounded-3xl shadow-2xl shadow-slate-950/30 w-full max-w-xl overflow-hidden flex flex-col max-h-[88vh] border border-slate-200/90 my-auto">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-white">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 text-slate-700 flex items-center justify-center font-bold shrink-0 shadow-2xs">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="9"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <h3 class="text-sm sm:text-base font-extrabold text-slate-800 leading-snug">
                                Riwayat & Detail Kendala
                            </h3>
                            <p class="text-xs text-slate-500 font-medium truncate">
                                {{ service.name }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="emit('close')"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Body (Scrollable) -->
                <div class="p-6 space-y-5 overflow-y-auto max-h-[calc(88vh-130px)]">
                    <!-- Status Overview Card -->
                    <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-2xs">
                        <div class="space-y-1">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Status Layanan & Penanganan</span>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    v-if="service.status === 'normal'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200 shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Normal
                                </span>
                                <span
                                    v-else-if="service.status === 'gangguan'"
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 shadow-2xs"
                                >
                                    <svg class="w-3.5 h-3.5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Terkendala
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200"
                                >
                                    Belum Dicek
                                </span>

                                <!-- Handling status badge -->
                                <span
                                    v-if="service.is_recheck_needed"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200 shadow-2xs"
                                >
                                    Perlu Pengecekan Ulang
                                </span>
                                <span
                                    v-else-if="service.handling_status === 'selesai'"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800 border border-emerald-200 shadow-2xs"
                                >
                                    Selesai Ditindaklanjuti
                                </span>
                                <span
                                    v-else-if="service.status === 'gangguan'"
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-rose-100 text-rose-800 border border-rose-200 shadow-2xs"
                                >
                                    Belum Ditindaklanjuti
                                </span>
                            </div>
                        </div>
                        <div v-if="service.checked_at" class="text-left sm:text-right text-[11px] text-slate-400">
                            <span>Pemeriksaan Terkini: </span>
                            <strong class="text-slate-600 block sm:inline">{{ service.checked_at }}</strong>
                        </div>
                    </div>

                    <!-- Timeline Alur Pemeriksaan & Penanganan Kendala -->
                    <div class="space-y-3.5">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                                    </svg>
                                    <span>Alur Log Pemeriksaan & Penanganan ({{ historyTimelineList.length }})</span>
                                </h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Dari log kendala awal, tindak lanjut penanganan Admin, hingga pengecekan baru.
                                </p>
                            </div>

                            <!-- Button Urutan / Toggle Sort Order -->
                            <button
                                type="button"
                                @click="toggleTimelineOrder"
                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[10px] font-bold bg-white hover:bg-slate-50 text-slate-600 border border-slate-200/90 shadow-2xs transition-all cursor-pointer"
                                :title="timelineOrder === 'asc' ? 'Klik untuk urutkan terbaru ke terlama' : 'Klik untuk urutkan kronologis awal ke baru'"
                            >
                                <svg class="w-3 h-3 text-slate-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                                </svg>
                                <span>{{ timelineOrder === 'asc' ? 'Kronologis (Awal → Baru)' : 'Terbaru → Lama' }}</span>
                            </button>
                        </div>

                        <!-- Timeline List with Connected Line -->
                        <div v-if="historyTimelineList && historyTimelineList.length > 0" class="relative pl-7 space-y-4 before:absolute before:left-3 before:top-3 before:bottom-3 before:w-0.5 before:bg-slate-200">
                            <div
                                v-for="(item, idx) in historyTimelineList"
                                :key="item.id || idx"
                                class="relative group"
                            >
                                <!-- Dot Icon / Indicator -->
                                <div
                                    class="absolute -left-7 top-1 w-6 h-6 rounded-full border-2 border-white shadow-xs flex items-center justify-center text-[10px] font-bold text-white transition-transform group-hover:scale-110"
                                    :class="[
                                        item.stage === 'initial_check'
                                            ? (item.status === 'normal' ? 'bg-emerald-500' : 'bg-rose-500')
                                            : item.stage === 'admin_handling'
                                                ? 'bg-amber-500'
                                                : (item.status === 'normal' ? 'bg-emerald-500' : 'bg-rose-500')
                                    ]"
                                >
                                    <span v-if="item.step_number">{{ item.step_number }}</span>
                                    <span v-else>{{ idx + 1 }}</span>
                                </div>

                                <!-- Card Container -->
                                <div
                                    class="rounded-2xl border p-4 space-y-3 transition-all shadow-2xs"
                                    :class="[
                                        item.stage === 'admin_handling'
                                            ? 'bg-amber-50/60 border-amber-200/90'
                                            : item.stage === 'initial_check'
                                                ? (item.status === 'normal' ? 'bg-white border-slate-200' : 'bg-rose-50/40 border-rose-200/90')
                                                : (item.status === 'normal' ? 'bg-emerald-50/40 border-emerald-200/90' : 'bg-rose-50/40 border-rose-200/90')
                                    ]"
                                >
                                    <!-- Header Card: Badge Tahapan + Waktu & Pelaku -->
                                    <div class="flex items-start justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <!-- Badge Stage -->
                                            <span
                                                class="px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide border shadow-2xs"
                                                :class="[
                                                    item.stage === 'admin_handling'
                                                        ? 'bg-amber-100 text-amber-900 border-amber-300'
                                                        : item.stage === 'initial_check'
                                                            ? (item.status === 'normal' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-100 text-rose-900 border-rose-300')
                                                            : (item.status === 'normal' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-100 text-rose-900 border-rose-300')
                                                ]"
                                            >
                                                {{ item.stage_label || (item.status === 'normal' ? 'Normal' : 'Terkendala') }}
                                            </span>

                                            <!-- Badge Hasil Terkini jika is_latest -->
                                            <span
                                                v-if="item.is_latest"
                                                class="inline-flex items-center px-2 py-0.5 rounded-md text-[9px] font-extrabold uppercase bg-primary text-white shadow-2xs"
                                            >
                                                Log Terbaru
                                            </span>
                                        </div>

                                        <!-- Tanggal & Waktu -->
                                        <div class="text-right">
                                            <span class="text-xs font-bold text-slate-700 block">
                                                {{ item.created_at || '-' }}
                                            </span>
                                            <span v-if="item.created_at_human" class="text-[10px] text-slate-400">
                                                {{ item.created_at_human }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Pelaku / Actor -->
                                    <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                        </svg>
                                        <span>{{ item.actor_role || 'Pemeriksa' }}:</span>
                                        <strong class="text-slate-700 font-semibold">{{ item.actor_name || 'Petugas' }}</strong>
                                    </div>

                                    <!-- Keterangan / Catatan -->
                                    <div class="bg-white/90 rounded-xl p-3 border border-slate-200/80 text-xs text-slate-700 leading-relaxed shadow-2xs">
                                        <p class="font-bold text-[10px] text-slate-400 uppercase tracking-wider mb-1">
                                            {{ item.stage === 'admin_handling' ? 'Catatan Tindak Lanjut Admin:' : 'Catatan Pemeriksaan:' }}
                                        </p>
                                        <p v-if="item.description" class="whitespace-pre-wrap font-medium text-slate-800">
                                            {{ item.description }}
                                        </p>
                                        <p v-else class="text-slate-400 italic">
                                            (Tidak ada catatan keterangan)
                                        </p>
                                    </div>

                                    <!-- Bukti Lampiran Kendala jika ada pada log ini -->
                                    <div v-if="item.attachment_url" class="space-y-2 pt-1 border-t border-slate-200/60">
                                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider flex items-center gap-1">
                                            <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                            Bukti Lampiran Pemeriksaan
                                        </p>

                                        <!-- Preview Gambar jika file gambar -->
                                        <div
                                            v-if="shouldShowPreview(item)"
                                            class="relative rounded-xl overflow-hidden bg-slate-100 border border-slate-200/90 p-1 flex items-center justify-center max-h-56 group/img"
                                        >
                                            <img
                                                :src="item.attachment_url"
                                                :alt="item.attachment_name || 'Bukti Kendala'"
                                                @error="onImageError(item.id || item.attachment_url)"
                                                class="max-h-52 object-contain rounded-lg w-auto mx-auto"
                                            />
                                            <a
                                                :href="item.attachment_url"
                                                target="_blank"
                                                class="absolute inset-0 bg-black/0 group-hover/img:bg-black/20 transition-all flex items-center justify-center opacity-0 group-hover/img:opacity-100 cursor-pointer"
                                            >
                                                <span class="px-2.5 py-1 rounded-lg bg-black/80 text-white text-[11px] font-semibold flex items-center gap-1 shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                    Buka Gambar Penuh
                                                </span>
                                            </a>
                                        </div>

                                        <!-- Chip File & Tombol Download/Buka -->
                                        <div class="flex items-center justify-between gap-2 p-2 bg-slate-50/80 rounded-xl border border-slate-200/80">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-7 h-7 rounded-lg bg-rose-50 border border-rose-200 text-rose-600 flex items-center justify-center shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                </div>
                                                <p class="text-xs font-semibold text-slate-800 truncate" :title="item.attachment_name">
                                                    {{ item.attachment_name || 'lampiran_kendala' }}
                                                </p>
                                            </div>
                                            <a
                                                :href="item.attachment_url"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold bg-primary text-white hover:bg-primary/90 transition-colors shadow-2xs shrink-0"
                                            >
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                <span>Buka / Unduh</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Pending Stage Indicator (Jika masih ada proses yang berjalan/menunggu) -->
                            <div v-if="service.pending_stage" class="relative group">
                                <!-- Pulse Dot -->
                                <div
                                    class="absolute -left-7 top-1.5 w-6 h-6 rounded-full border-2 border-white shadow-xs flex items-center justify-center text-[10px] font-bold text-white animate-pulse"
                                    :class="service.pending_stage.type === 'solved' ? 'bg-emerald-500' : (service.pending_stage.type === 'waiting_recheck' ? 'bg-amber-500' : 'bg-rose-500')"
                                >
                                    <span v-if="service.pending_stage.type === 'solved'">✓</span>
                                    <span v-else-if="service.pending_stage.type === 'waiting_recheck'">🔄</span>
                                    <span v-else>⏳</span>
                                </div>

                                <div
                                    class="rounded-2xl border-2 border-dashed p-4 space-y-2.5"
                                    :class="[
                                        service.pending_stage.type === 'solved'
                                            ? 'bg-emerald-50/50 border-emerald-300'
                                            : service.pending_stage.type === 'waiting_recheck'
                                                ? 'bg-amber-50/70 border-amber-300'
                                                : 'bg-rose-50/40 border-rose-300'
                                    ]"
                                >
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wide border shadow-2xs"
                                                :class="service.pending_stage.badge_class || 'bg-slate-100 text-slate-700 border-slate-200'"
                                            >
                                                {{ service.pending_stage.badge }}
                                            </span>
                                            <h5 class="text-xs font-bold text-slate-800">
                                                {{ service.pending_stage.title }}
                                            </h5>
                                        </div>
                                    </div>

                                    <p class="text-xs text-slate-600 leading-relaxed font-medium">
                                        {{ service.pending_stage.description }}
                                    </p>

                                    <div v-if="service.pending_stage.type === 'waiting_recheck' && !(periodInfo?.status !== 'proses' || periodInfo?.is_overdue)" class="pt-1">
                                        <button
                                            type="button"
                                            @click="emit('recheck', service)"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl font-bold text-xs bg-amber-500 hover:bg-amber-600 text-white shadow-sm shadow-amber-500/20 transition-all cursor-pointer"
                                        >
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span>Lakukan Pengecekan Ulang Sekarang</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Fallback jika belum ada log sama sekali -->
                        <div v-else class="text-xs text-slate-400 italic p-6 bg-slate-50 rounded-2xl border border-dashed border-slate-200 text-center">
                            Belum ada catatan riwayat pemeriksaan untuk layanan ini.
                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="px-6 py-3.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-2.5 rounded-b-3xl">
                    <button
                        type="button"
                        @click="emit('close')"
                        class="px-4.5 py-2 rounded-xl font-semibold text-xs text-slate-600 bg-white border border-slate-200 hover:bg-slate-100 hover:text-slate-800 transition-colors cursor-pointer"
                    >
                        Tutup
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="service.is_recheck_needed && !(periodInfo?.status !== 'proses' || periodInfo?.is_overdue)"
                            type="button"
                            @click="emit('recheck', service)"
                            class="px-4.5 py-2 rounded-xl font-bold text-xs text-white bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-500/20 transition-all flex items-center gap-1.5 cursor-pointer active:scale-95"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            <span>Cek Ulang Layanan</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

