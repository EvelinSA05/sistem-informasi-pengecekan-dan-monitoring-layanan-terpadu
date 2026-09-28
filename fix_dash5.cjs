const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<!-- Progress Pengujian Card (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
 <div>
 <h3 class="text-base sm:text-lg font-bold text-slate-800">Progress Pengujian Saya</h3>
 <p class="text-xs text-slate-400 mt-0.5">Persentase penyelesaian layanan yang ditugaskan pada periode aktif</p>
 </div>
 <span class="inline-flex items-center gap-1.5 self-start sm:self-auto px-3.5 py-1.5 rounded-full text-xs font-black shadow-2xs" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-primary text-primary border border-primary'">
 <span class="w-2 h-2 rounded-full" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-500' : 'bg-primary'"></span>
 {{ props.stats.progress_percent }}% Selesai
 </span>
 </div>

 <!-- Progress Bar with Gradient -->
 <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden p-0.5 mb-2.5 border border-slate-200/60">
 <div
 class="h-full rounded-full transition-all duration-700 ease-out shadow-xs"
 :class="props.stats.progress_percent >= 100 ? 'bg-primary ' : (props.stats.progress_percent >= 50 ? 'bg-primary ' : 'bg-primary')"
 :style="{ width: props.stats.progress_percent + '%' }"
 ></div>
 </div>

 <div class="flex items-center justify-between text-xs text-slate-500 font-medium flex-wrap gap-2">
 <p>
 <strong class="font-bold text-slate-800">{{ props.stats.checked }}</strong> dari <strong class="font-bold text-slate-800">{{ props.stats.total_assigned }}</strong> layanan telah diuji.
 </p>
 <p v-if="props.stats.remaining_count > 0" class="text-amber-700 font-semibold">
 {{ props.stats.remaining_count }} layanan masih perlu diuji
 </p>
 <p v-else-if="props.stats.total_assigned > 0" class="text-emerald-600 font-semibold">
 Seluruh tugas telah selesai diuji 🎉
 </p>
 </div>
 </div>

 <!-- Mini Stat Badges -->
 <div class="grid grid-cols-4 gap-2.5 sm:gap-3 mt-6 pt-5 border-t border-slate-100 text-center">
 <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200/70">
 <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Ditugaskan</p>
 <p class="text-base sm:text-lg font-black text-slate-700 mt-0.5">{{ props.stats.total_assigned }}</p>
 </div>
 <div class="p-3 rounded-2xl bg-emerald-50/70 border border-emerald-200/70">
 <p class="text-[11px] text-emerald-700 font-bold uppercase tracking-wider">Normal</p>
 <p class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">{{ props.stats.normal }}</p>
 </div>
 <div class="p-3 rounded-2xl bg-rose-50/70 border border-rose-200/70">
 <p class="text-[11px] text-rose-700 font-bold uppercase tracking-wider">Kendala</p>
 <p class="text-base sm:text-lg font-black text-rose-700 mt-0.5">{{ props.stats.issues }}</p>
 </div>
 <div class="p-3 rounded-2xl bg-primary/70 border border-primary/70">
 <p class="text-[11px] text-primary font-bold uppercase tracking-wider">Pending</p>
 <p class="text-base sm:text-lg font-black text-primary mt-0.5">{{ props.stats.pending }}</p>
 </div>
 </div>
 </div>`;

const newCode = `<!-- Progress Pengujian Card (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-4">
 <div>
 <h3 class="text-lg font-extrabold text-slate-800">Progress Pengujian Saya</h3>
 <p class="text-sm text-slate-500 mt-0.5">Persentase penyelesaian layanan yang ditugaskan pada periode aktif</p>
 </div>
 <span class="inline-flex items-center gap-1.5 self-start sm:self-auto px-3.5 py-1.5 rounded-full text-xs font-black shadow-sm transition-all" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-primary/5 text-primary border border-primary/20'">
 <span class="w-2 h-2 rounded-full" :class="props.stats.progress_percent >= 100 ? 'bg-emerald-500 animate-pulse' : 'bg-primary'"></span>
 {{ props.stats.progress_percent }}% Selesai
 </span>
 </div>

 <!-- Progress Bar with Gradient -->
 <div class="w-full h-3.5 bg-slate-100 rounded-full overflow-hidden p-0.5 mb-2.5 border border-slate-200/60">
 <div
 class="h-full rounded-full transition-all duration-700 ease-out shadow-sm"
 :class="props.stats.progress_percent >= 100 ? 'bg-emerald-500 shadow-emerald-400/50' : 'bg-gradient-to-r from-blue-400 to-indigo-500 shadow-blue-400/50'"
 :style="{ width: props.stats.progress_percent + '%' }"
 ></div>
 </div>

 <div class="flex items-center justify-between text-xs text-slate-500 font-medium flex-wrap gap-2">
 <p>
 <strong class="font-bold text-slate-800">{{ props.stats.checked }}</strong> dari <strong class="font-bold text-slate-800">{{ props.stats.total_assigned }}</strong> layanan telah diuji.
 </p>
 <p v-if="props.stats.remaining_count > 0" class="text-amber-600 font-semibold">
 {{ props.stats.remaining_count }} layanan masih perlu diuji
 </p>
 <p v-else-if="props.stats.total_assigned > 0" class="text-emerald-600 font-semibold">
 Seluruh tugas telah selesai diuji 🎉
 </p>
 </div>
 </div>

 <!-- Mini Stat Badges -->
 <div class="grid grid-cols-4 gap-2.5 sm:gap-3 mt-6 pt-5 border-t border-slate-100 text-center">
 <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 shadow-sm transition-all hover:bg-slate-100/70 cursor-default">
 <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Ditugaskan</p>
 <p class="text-base sm:text-lg font-black text-slate-700 mt-0.5">{{ props.stats.total_assigned }}</p>
 </div>
 <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200/70 shadow-sm transition-all hover:bg-emerald-100/50 cursor-default">
 <p class="text-[11px] text-emerald-700 font-bold uppercase tracking-wider">Normal</p>
 <p class="text-base sm:text-lg font-black text-emerald-700 mt-0.5">{{ props.stats.normal }}</p>
 </div>
 <div class="p-3 rounded-xl bg-rose-50 border border-rose-200/70 shadow-sm transition-all hover:bg-rose-100/50 cursor-default">
 <p class="text-[11px] text-rose-700 font-bold uppercase tracking-wider">Kendala</p>
 <p class="text-base sm:text-lg font-black text-rose-700 mt-0.5">{{ props.stats.issues }}</p>
 </div>
 <div class="p-3 rounded-xl bg-blue-50 border border-blue-200/70 shadow-sm transition-all hover:bg-blue-100/50 cursor-default">
 <p class="text-[11px] text-blue-700 font-bold uppercase tracking-wider">Pending</p>
 <p class="text-base sm:text-lg font-black text-blue-700 mt-0.5">{{ props.stats.pending }}</p>
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
