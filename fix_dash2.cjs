const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<!-- Period Info Card -->
 <div class="shrink-0 flex items-center">
 <div class="w-full sm:w-auto px-6 py-4 rounded-xl bg-white/10 border border-white/20 backdrop-blur-sm text-left sm:text-right">
 <div class="flex items-center sm:justify-end gap-2 mb-1">
 <span class="w-2 h-2 rounded-full" :class="periodInfo ? (periodInfo.status === 'selesai' ? 'bg-emerald-400' : 'bg-amber-300 animate-pulse') : 'bg-slate-400'"></span>
 <p class="text-[11px] font-bold text-cyan-100 tracking-widest uppercase">
 {{ periodInfo ? (periodInfo.status === 'selesai' ? 'Periode Selesai' : 'Periode Berjalan') : 'Status Saat Ini' }}
 </p>
 </div>
 <p class="text-lg sm:text-xl font-bold text-white capitalize leading-snug">
 {{ periodInfo ? currentMonthYear : 'Tidak Ada Penugasan Aktif' }}
 </p>
 <div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-white flex-wrap">
 <span v-if="periodInfo?.date" class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1 rounded-lg border border-white/20">
 <svg class="w-3.5 h-3.5 text-cyan-200" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
 {{ periodInfo.period_title }}
 </span>
 <span v-if="periodInfo && periodInfo.status === 'proses'" class="inline-flex items-center gap-1 bg-white/10 px-3 py-1 rounded-lg border border-white/20">
 <span v-if="periodInfo.days_remaining > 0">⏱️ {{ periodInfo.days_remaining }} hari lagi</span>
 <span v-else-if="periodInfo.days_remaining === 0">⏱️ Hari ini</span>
 <span v-else>⚠️ Lewat target</span>
 </span>
 </div>
 </div>
 </div>`;

const newCode = `<!-- Period Info Card -->
 <div class="shrink-0 flex items-center">
 <div class="w-full sm:w-auto px-6 py-4 rounded-2xl bg-white/15 backdrop-blur-md border border-white/25 shadow-lg text-left sm:text-right flex flex-col items-center sm:items-end">
 <div class="flex items-center sm:justify-end gap-2 mb-1">
 <span class="w-2 h-2 rounded-full" :class="periodInfo ? (periodInfo.status === 'selesai' ? 'bg-emerald-400' : 'bg-amber-300 animate-pulse') : 'bg-slate-400'"></span>
 <p class="text-[11px] font-bold text-cyan-200 tracking-widest uppercase">
 {{ periodInfo ? (periodInfo.status === 'selesai' ? 'Periode Selesai' : 'Periode Berjalan') : 'Status Saat Ini' }}
 </p>
 </div>
 <p class="text-lg sm:text-xl font-extrabold text-white capitalize leading-snug">
 {{ periodInfo ? currentMonthYear : 'Tidak Ada Penugasan Aktif' }}
 </p>
 <div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-blue-100/90 flex-wrap">
 <span v-if="periodInfo?.date" class="inline-flex items-center gap-1.5 bg-black/20 px-3 py-1 rounded-xl border border-white/15 shadow-inner">
 <svg class="w-3.5 h-3.5 text-cyan-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
 {{ periodInfo.period_title }}
 </span>
 <span v-if="periodInfo && periodInfo.status === 'proses'" class="inline-flex items-center gap-1 bg-amber-400/25 text-amber-200 px-3 py-1 rounded-xl border border-amber-300/35 shadow-inner">
 <span v-if="periodInfo.days_remaining > 0">⏱️ {{ periodInfo.days_remaining }} hari lagi</span>
 <span v-else-if="periodInfo.days_remaining === 0">⏱️ Hari ini</span>
 <span v-else>⚠️ Lewat target</span>
 </span>
 </div>
 </div>
 </div>`;

let c2 = c.replace(oldCode, newCode);
if (c === c2) {
    console.log('FAIL REPLACE');
} else {
    fs.writeFileSync('resources/js/Pages/Dashboard.vue', c2);
    console.log('SUCCESS REPLACE');
}
