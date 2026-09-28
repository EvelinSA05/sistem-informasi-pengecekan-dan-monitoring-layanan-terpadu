const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<!-- ── 5. ROW 3: "Tugas Prioritas" & "Aktivitas Terbaru" ── -->
 <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

 <!-- Tugas Prioritas (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
 <div>
 <h3 class="text-base font-bold text-slate-800">Tugas Prioritas</h3>
 <p class="text-xs text-slate-400">5 layanan prioritas yang memerlukan pengujian segera</p>
 </div>
 <Link
 :href="route('tester.services.index')"
 class="text-xs text-primary hover:text-primary font-bold inline-flex items-center gap-1 transition-colors"
 >
 Lihat Semua ({{ props.stats.total_assigned }})
 </Link>
 </div>

 <div v-if="props.priorityTasks.length > 0" class="space-y-3">
 <div
 v-for="(task, idx) in props.priorityTasks"
 :key="task.id"
 class="p-3.5 rounded-2xl bg-slate-50/70 border border-slate-200/70 hover:bg-primary/30 hover:border-primary transition-all flex items-center justify-between gap-3"
 >
 <div class="flex items-center gap-3 min-w-0">
 <span class="w-6 h-6 rounded-lg bg-slate-200/80 text-slate-600 text-xs font-bold flex items-center justify-center shrink-0">
 {{ idx + 1 }}
 </span>
 <div class="min-w-0">
 <div class="flex items-center gap-2 flex-wrap">
 <h4 class="text-xs sm:text-sm font-bold text-slate-800 truncate">{{ task.name }}</h4>
 <span class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-white border border-slate-200 text-slate-600">{{ task.category }}</span>
 <span v-if="task.level" class="text-[10px] font-semibold px-2 py-0.5 rounded-md bg-primary border border-primary text-primary">{{ task.level }}</span>
 </div>
 <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5">
 <a v-if="task.url" :href="task.url" target="_blank" class="text-primary hover:underline truncate max-w-[220px]">
 {{ task.url }}
 </a>
 <span v-else>-</span>
 </div>
 </div>
 </div>

 <div class="shrink-0 flex items-center gap-2">
 <span
 class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold"
 :class="task.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (task.status === 'gangguan' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
 >
 {{ task.status === 'normal' ? 'NORMAL' : (task.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
 </span>
 <button
 v-if="task.status === 'pending'"
 type="button"
 @click="openCheckModal(task)"
 class="inline-flex items-center justify-center gap-1 px-3.5 py-1.5 rounded-xl font-bold text-xs bg-primary text-white shadow-xs hover:shadow transition-all active:scale-95 cursor-pointer border border-primary"
 title="Lakukan Pengecekan Layanan"
 >
 <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 Cek
 </button>
 <button
 v-else
 type="button"
 @click="openCheckModal(task)"
 class="inline-flex items-center justify-center gap-1 px-3 py-1.5 rounded-xl font-semibold text-xs bg-slate-50 hover:bg-slate-100 text-slate-700 border border-slate-200 hover:border-slate-300 shadow-2xs transition-all active:scale-95 cursor-pointer"
 title="Ubah Hasil Pengecekan"
 >
 <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
 </svg>
 Ubah
 </button>
 </div>
 </div>
 </div>

 <div v-else class="py-10 text-center text-slate-400">
 <p class="text-xs">Belum ada penugasan layanan untuk periode ini.</p>
 </div>
 </div>
 </div>`;

const newCode = `<!-- ── 5. ROW 3: "Tugas Prioritas" & "Aktivitas Terbaru" ── -->
 <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

 <!-- Tugas Prioritas (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
 <div>
 <h3 class="text-lg font-extrabold text-slate-800">Tugas Prioritas</h3>
 <p class="text-sm text-slate-500 mt-0.5">5 layanan prioritas yang memerlukan pengujian segera</p>
 </div>
 <Link
 :href="route('tester.services.index')"
 class="text-sm text-blue-600 hover:text-blue-700 hover:underline font-bold inline-flex items-center gap-1 transition-all"
 >
 Lihat Semua ({{ props.stats.total_assigned }})
 </Link>
 </div>

 <div v-if="props.priorityTasks.length > 0" class="space-y-3">
 <div
 v-for="(task, idx) in props.priorityTasks"
 :key="task.id"
 class="p-3.5 rounded-2xl bg-white border border-slate-200/80 shadow-xs hover:bg-slate-50 hover:border-slate-300 hover:shadow-sm transition-all flex items-center justify-between gap-3 group"
 >
 <div class="flex items-center gap-3.5 min-w-0">
 <span class="w-7 h-7 rounded-lg bg-slate-100 text-slate-500 text-xs font-extrabold flex items-center justify-center shrink-0 border border-slate-200/60 shadow-xs">
 {{ idx + 1 }}
 </span>
 <div class="min-w-0">
 <div class="flex items-center gap-2.5 flex-wrap">
 <h4 class="text-sm font-extrabold text-slate-700 truncate group-hover:text-blue-700 transition-colors">{{ task.name }}</h4>
 <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-white border border-slate-200 text-slate-500 shadow-xs">{{ task.category }}</span>
 <span v-if="task.level" class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 border border-blue-200 text-blue-700 shadow-xs">{{ task.level }}</span>
 </div>
 <div class="flex items-center gap-2 text-xs text-slate-400 mt-1">
 <a v-if="task.url" :href="task.url" target="_blank" class="text-blue-500 hover:text-blue-700 hover:underline truncate max-w-[220px]">
 {{ task.url }}
 </a>
 <span v-else>-</span>
 </div>
 </div>
 </div>

 <div class="shrink-0 flex items-center gap-3">
 <span
 class="hidden sm:inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-extrabold tracking-wider"
 :class="task.status === 'normal' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : (task.status === 'gangguan' ? 'bg-rose-50 text-rose-700 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200')"
 >
 {{ task.status === 'normal' ? 'NORMAL' : (task.status === 'gangguan' ? 'KENDALA' : 'PENDING') }}
 </span>
 <button
 v-if="task.status === 'pending'"
 type="button"
 @click="openCheckModal(task)"
 class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl font-bold text-xs bg-primary hover:bg-primary/90 text-white shadow-sm hover:shadow-md shadow-blue-200 transition-all active:scale-95 cursor-pointer"
 title="Lakukan Pengecekan Layanan"
 >
 <svg class="w-3.5 h-3.5 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
 </svg>
 Cek
 </button>
 <button
 v-else
 type="button"
 @click="openCheckModal(task)"
 class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl font-bold text-xs bg-white hover:bg-slate-50 text-slate-600 border border-slate-200 hover:border-slate-300 shadow-sm transition-all active:scale-95 cursor-pointer"
 title="Ubah Hasil Pengecekan"
 >
 <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
 </svg>
 Ubah
 </button>
 </div>
 </div>
 </div>

 <div v-else class="py-12 flex flex-col items-center justify-center text-slate-400">
 <svg class="w-12 h-12 text-slate-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
 <p class="text-sm font-medium">Belum ada penugasan layanan untuk periode ini.</p>
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
