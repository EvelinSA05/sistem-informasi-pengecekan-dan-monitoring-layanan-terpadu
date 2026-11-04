<script setup>
defineProps({
    show: { type: Boolean, default: false },
    issue: { type: Object, default: null },
});

const emit = defineEmits(['close']);

function isImageFile(filename) {
    if (!filename) return false;
    const ext = String(filename).split('.').pop().toLowerCase();
    return ['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(ext);
}

function getFileExtension(filename) {
    if (!filename) return 'FILE';
    return (String(filename).split('.').pop() || 'FILE').toUpperCase();
}
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
            <div v-if="show && issue" class="fixed inset-0 z-50 flex items-center justify-center p-4 overflow-y-auto">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="emit('close')"></div>

                <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8 overflow-hidden z-10 border border-slate-100">
                    <!-- Header Modal -->
                    <div class="relative bg-gradient-to-r from-rose-500 via-rose-600 to-amber-600 p-6 text-white overflow-hidden">
                        <div class="absolute -right-10 -top-10 w-40 h-40 bg-white/10 rounded-full blur-2xl"></div>
                        <div class="absolute right-20 -bottom-10 w-24 h-24 bg-black/10 rounded-full blur-xl"></div>

                        <div class="relative flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center border border-white/30 shadow-inner shrink-0">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <span class="inline-block text-[11px] font-bold uppercase tracking-wider text-rose-100 bg-white/15 px-2.5 py-0.5 rounded-full mb-1">
                                        Hasil Pengecekan Tester
                                    </span>
                                    <h3 class="text-xl font-bold text-white leading-tight">Detail Kendala Layanan</h3>
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

                    <!-- Body Modal -->
                    <div class="p-6 space-y-5 max-h-[calc(100vh-210px)] overflow-y-auto">
                        <!-- 1. Info Layanan & URL -->
                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                            <div class="flex items-start justify-between gap-3 flex-wrap">
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap mb-1">
                                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 shadow-2xs">
                                            {{ issue.category }}
                                        </span>
                                        <span v-if="issue.level && issue.level !== '-'" class="text-xs font-semibold px-2.5 py-0.5 rounded-md bg-white border border-slate-200 text-slate-700 shadow-2xs">
                                            Level {{ issue.level }}
                                        </span>
                                    </div>
                                    <h4 class="text-base font-bold text-slate-800">{{ issue.service_name }}</h4>
                                </div>

                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200 shadow-2xs">
                                    KENDALA
                                </span>
                            </div>

                            <div v-if="issue.service_url" class="mt-2.5 pt-2.5 border-t border-slate-200/60 flex items-center gap-2">
                                <span class="text-xs font-medium text-slate-400 shrink-0">Tautan Layanan:</span>
                                <a
                                    :href="issue.service_url"
                                    target="_blank"
                                    class="text-xs text-primary hover:text-blue-700 font-semibold truncate inline-flex items-center gap-1 hover:underline"
                                >
                                    {{ issue.service_url }}
                                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>
                            </div>
                        </div>

                        <!-- 2. Metadata Tester & Tanggal Pengecekan -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center font-bold text-sm shrink-0 border border-primary/20">
                                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tester Pengecek</p>
                                    <p class="text-xs font-bold text-slate-800 truncate">{{ issue.tester_name || 'Tester' }}</p>
                                    <p class="text-[11px] text-slate-500 truncate">{{ issue.tester_bidang || 'Bidang Pengujian' }}</p>
                                </div>
                            </div>

                            <div class="p-3.5 rounded-xl border border-slate-200 bg-white flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm shrink-0 border border-amber-200">
                                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Waktu Pengecekan</p>
                                    <p class="text-xs font-bold text-slate-800">{{ issue.checked_at || '-' }}</p>
                                    <p class="text-[11px] text-slate-500">Status tercatat: Kendala</p>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Catatan / Keterangan Kendala -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                                Catatan / Keterangan Kendala
                            </label>
                            <div class="p-4 rounded-2xl bg-rose-50/50 border border-rose-200 text-slate-700 text-xs leading-relaxed font-medium whitespace-pre-wrap">
                                {{ issue.description || 'Tidak ada catatan keterangan kendala yang disertakan.' }}
                            </div>
                        </div>

                        <!-- 4. Lampiran Bukti Kendala (Attachment) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                Lampiran Bukti Kendala (Attachment)
                            </label>

                            <div v-if="issue.attachment || issue.attachment_url" class="rounded-2xl border border-slate-200 bg-white p-4 space-y-3 shadow-2xs">
                                <div class="flex items-center justify-between gap-3 flex-wrap">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="isImageFile(issue.attachment_name || issue.attachment) ? 'bg-indigo-50 text-indigo-600 border border-indigo-200' : 'bg-rose-50 text-rose-600 border border-rose-200'">
                                            <svg v-if="isImageFile(issue.attachment_name || issue.attachment)" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-xs font-bold text-slate-800 truncate max-w-xs sm:max-w-md" :title="issue.attachment_name || issue.attachment">
                                                {{ issue.attachment_name || (issue.attachment ? issue.attachment.split('/').pop() : 'attachment_file') }}
                                            </p>
                                            <div class="flex items-center gap-2 mt-0.5">
                                                <span class="text-[10px] font-bold uppercase px-1.5 py-0.2 rounded bg-slate-100 text-slate-600">
                                                    {{ getFileExtension(issue.attachment_name || issue.attachment) }}
                                                </span>
                                                <span class="text-[11px] text-slate-400">Bukti Pengujian Tester</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 shrink-0">
                                        <a
                                            v-if="issue.attachment_url"
                                            :href="issue.attachment_url"
                                            target="_blank"
                                            class="px-3 py-1.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition-colors inline-flex items-center gap-1.5 cursor-pointer shadow-2xs"
                                            title="Buka file asli di tab browser baru"
                                        >
                                            <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            Buka File
                                        </a>
                                        <a
                                            :href="route('admin.periods.attachment.download', issue.id)"
                                            class="px-3.5 py-1.5 rounded-xl bg-primary hover:bg-[#1d4ed8] text-white text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-md shadow-primary/20 active:scale-95 cursor-pointer"
                                            title="Unduh file bukti kendala ke penyimpanan lokal"
                                        >
                                            <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                            Unduh Attachment
                                        </a>
                                    </div>
                                </div>

                                <div
                                    v-if="isImageFile(issue.attachment_name || issue.attachment) && issue.attachment_url"
                                    class="rounded-xl overflow-hidden border border-slate-200 bg-slate-900/5 relative group p-2"
                                >
                                    <img
                                        :src="issue.attachment_url"
                                        :alt="issue.attachment_name || 'Bukti Kendala'"
                                        class="w-full max-h-72 object-contain mx-auto rounded-lg transition-transform duration-300 group-hover:scale-[1.01]"
                                    />
                                    <a
                                        :href="issue.attachment_url"
                                        target="_blank"
                                        class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-all flex items-center justify-center opacity-0 group-hover:opacity-100 cursor-pointer"
                                    >
                                        <span class="px-3 py-1.5 rounded-xl bg-black/75 text-white text-xs font-semibold backdrop-blur-sm flex items-center gap-1.5 shadow-lg">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                            Buka Ukuran Penuh
                                        </span>
                                    </a>
                                </div>

                                <div
                                    v-else
                                    class="p-4 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center font-bold text-xs uppercase shrink-0">
                                            {{ getFileExtension(issue.attachment_name || issue.attachment) }}
                                        </div>
                                        <div>
                                            <p class="text-xs font-semibold text-slate-700">Dokumen Lampiran Bukti Kendala</p>
                                            <p class="text-[11px] text-slate-400">File dokumen dapat diunduh atau dibuka langsung melalui browser.</p>
                                        </div>
                                    </div>
                                    <a
                                        :href="route('admin.periods.attachment.download', issue.id)"
                                        class="px-3 py-1.5 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-50 inline-flex items-center gap-1 shadow-2xs shrink-0"
                                    >
                                        <svg class="w-3.5 h-3.5 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                        Unduh File
                                    </a>
                                </div>
                            </div>

                            <div v-else class="p-5 rounded-2xl border border-slate-200 bg-slate-50 text-center">
                                <div class="w-9 h-9 rounded-full bg-slate-200/80 text-slate-500 flex items-center justify-center mx-auto mb-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                </div>
                                <p class="text-xs font-bold text-slate-700">Tidak Ada Lampiran File</p>
                                <p class="text-[11px] text-slate-400 mt-0.5">Tester tidak mengunggah file bukti pendukung untuk kendala ini.</p>
                            </div>
                        </div>

                        <!-- 5. Timeline Alur Pemeriksaan & Penanganan -->
                        <div v-if="issue.timeline && issue.timeline.length > 0" class="pt-2 border-t border-slate-200/80 space-y-3">
                            <div class="flex items-center justify-between">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-primary" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 3"/>
                                    </svg>
                                    Alur Log Pemeriksaan & Penanganan ({{ issue.timeline.length }})
                                </label>
                                <span class="text-[11px] text-slate-400">Kronologis Awal → Baru</span>
                            </div>

                            <div class="relative pl-6 space-y-3.5 before:absolute before:left-2.5 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-200">
                                <div
                                    v-for="(log, idx) in issue.timeline"
                                    :key="log.id || idx"
                                    class="relative group"
                                >
                                    <div
                                        class="absolute -left-6 top-1 w-4 h-4 rounded-full border-2 border-white shadow-2xs flex items-center justify-center text-[8px] font-bold text-white"
                                        :class="log.stage === 'admin_handling' ? 'bg-amber-500' : (log.status === 'normal' ? 'bg-emerald-500' : 'bg-rose-500')"
                                    >
                                        {{ idx + 1 }}
                                    </div>

                                    <div
                                        class="p-3.5 rounded-xl border space-y-2 text-xs shadow-2xs"
                                        :class="log.stage === 'admin_handling' ? 'bg-amber-50/60 border-amber-200' : (log.status === 'normal' ? 'bg-emerald-50/50 border-emerald-200' : 'bg-rose-50/50 border-rose-200')"
                                    >
                                        <div class="flex items-center justify-between gap-2 flex-wrap">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border shadow-2xs"
                                                    :class="log.stage === 'admin_handling' ? 'bg-amber-100 text-amber-900 border-amber-300' : (log.status === 'normal' ? 'bg-emerald-100 text-emerald-900 border-emerald-300' : 'bg-rose-100 text-rose-900 border-rose-300')"
                                                >
                                                    {{ log.stage_label || log.status_label }}
                                                </span>
                                                <span v-if="log.is_latest" class="px-1.5 py-0.2 rounded text-[9px] font-bold bg-primary text-white">
                                                    Log Terbaru
                                                </span>
                                            </div>
                                            <span class="text-[11px] text-slate-400 font-medium">
                                                {{ log.created_at || '-' }}
                                            </span>
                                        </div>

                                        <div class="flex items-center gap-1.5 text-[11px] text-slate-500">
                                            <span>{{ log.actor_role }}: </span>
                                            <strong class="text-slate-700 font-semibold">{{ log.actor_name }}</strong>
                                        </div>

                                        <div class="bg-white/90 rounded-lg p-2.5 border border-slate-200/80 leading-relaxed whitespace-pre-wrap font-medium text-slate-700">
                                            {{ log.description || '(Tidak ada catatan keterangan)' }}
                                        </div>

                                        <div v-if="log.attachment_url" class="pt-1">
                                            <a
                                                :href="log.attachment_url"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-700 hover:text-rose-800 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg transition-colors border border-rose-200/60"
                                            >
                                                <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                                <span>Lampiran: {{ log.attachment_name || 'Buka Bukti' }}</span>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Modal -->
                    <div class="p-4 sm:p-5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-3">
                        <span class="text-xs text-slate-400">
                            ID Layanan Periode: <strong class="text-slate-600">#{{ issue.id }}</strong>
                        </span>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="emit('close')"
                                class="px-4 py-2 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-100 transition-colors cursor-pointer"
                            >
                                Tutup
                            </button>
                            <a
                                v-if="issue.attachment || issue.attachment_url"
                                :href="route('admin.periods.attachment.download', issue.id)"
                                class="px-4 py-2 rounded-xl bg-primary hover:bg-[#1d4ed8] text-white text-xs font-bold inline-flex items-center gap-1.5 transition-all shadow-md shadow-primary/20 active:scale-95 cursor-pointer"
                            >
                                <svg class="w-3.5 h-3.5 fill-none stroke-current" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                Unduh Bukti Kendala
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
