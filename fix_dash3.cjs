const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-blue-100/90 flex-wrap">
 <span v-if="periodInfo?.date" class="inline-flex items-center gap-1.5 bg-black/20 px-3 py-1 rounded-xl border border-white/15 shadow-inner">
 <svg class="w-3.5 h-3.5 text-cyan-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
 {{ periodInfo.period_title }}
 </span>
 <span v-if="periodInfo && periodInfo.status === 'proses'" class="inline-flex items-center gap-1 bg-amber-400/25 text-amber-200 px-3 py-1 rounded-xl border border-amber-300/35 shadow-inner">
 <span v-if="periodInfo.days_remaining > 0">⏱️ {{ periodInfo.days_remaining }} hari lagi</span>
 <span v-else-if="periodInfo.days_remaining === 0">⏱️ Hari ini</span>
 <span v-else>⚠️ Lewat target</span>
 </span>
 </div>`;

const newCode = `<div class="mt-2.5 flex items-center sm:justify-end gap-2.5 text-xs font-medium text-white/90 flex-wrap">
 <span v-if="periodInfo?.date" class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-full border border-white/10 backdrop-blur-sm shadow-sm transition-all hover:bg-white/15">
 <svg class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path stroke-linecap="round" d="M16 2v4M8 2v4M3 10h18"/></svg>
 {{ periodInfo.period_title }}
 </span>
 <span v-if="periodInfo && periodInfo.status === 'proses'" class="inline-flex items-center gap-1.5 bg-white/10 px-3 py-1.5 rounded-full border border-white/10 backdrop-blur-sm shadow-sm transition-all hover:bg-white/15" :class="{ 'text-rose-100 bg-rose-500/20 border-rose-400/30': periodInfo.days_remaining < 0 }">
 <svg v-if="periodInfo.days_remaining >= 0" class="w-3.5 h-3.5 opacity-70" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
 <svg v-else class="w-3.5 h-3.5 opacity-80" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
 <span v-if="periodInfo.days_remaining > 0">{{ periodInfo.days_remaining }} Hari Lagi</span>
 <span v-else-if="periodInfo.days_remaining === 0">Batas Waktu Hari Ini</span>
 <span v-else>Terlewat</span>
 </span>
 </div>`;

let c2 = c.replace(oldCode, newCode);
if (c === c2) {
    console.log('FAIL REPLACE');
} else {
    fs.writeFileSync('resources/js/Pages/Dashboard.vue', c2);
    console.log('SUCCESS REPLACE');
}
