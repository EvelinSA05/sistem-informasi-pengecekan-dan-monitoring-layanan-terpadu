const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<!-- ── 4. ROW 2: "Perlu Tindakan" & "Aksi Cepat" ────────── -->
 <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

 <!-- Section Perlu Tindakan (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
 <div class="flex items-center gap-2.5">
 <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center font-bold">
 <svg class="w-4 h-4 stroke-[2.5]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 </div>
 <div>
 <h3 class="text-base font-bold text-slate-800">Perlu Tindakan</h3>
 <p class="text-xs text-slate-400">Peringatan dan hal yang membutuhkan perhatian Anda</p>
 </div>
 </div>
 <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3 py-1 rounded-full">
 {{ props.actionRequired.length }} Item
 </span>
 </div>

 <!-- Actionable Alerts List -->
 <div v-if="props.actionRequired.length > 0" class="space-y-3">
 <div
 v-for="(action, i) in props.actionRequired"
 :key="i"
 class="p-4 rounded-2xl border transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
 :class="action.severity === 'danger' ? 'bg-rose-50/60 border-rose-200 text-rose-900' : (action.severity === 'urgent' ? 'bg-amber-50/80 border-amber-300 text-amber-950' : 'bg-primary/60 border-primary text-primary')"
 >
 <div class="flex items-start gap-3 min-w-0">
 <div
 class="w-7 h-7 rounded-xl flex items-center justify-center shrink-0 mt-0.5 font-bold text-xs"
 :class="action.severity === 'danger' ? 'bg-rose-600 text-white' : (action.severity === 'urgent' ? 'bg-amber-500 text-white' : 'bg-primary text-white')"
 >
 {{ i + 1 }}
 </div>
 <div class="min-w-0">
 <div class="flex items-center gap-2 flex-wrap">
 <h4 class="text-xs sm:text-sm font-bold truncate">{{ action.title }}</h4>
 <span class="text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md bg-white/80 border border-black/5 shadow-2xs">{{ action.badge }}</span>
 </div>
 <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ action.description }}</p>
 </div>
 </div>

 <Link
 :href="action.action_url"
 class="shrink-0 self-start sm:self-center px-4 py-2 rounded-xl text-xs font-bold shadow-xs hover:shadow transition-all inline-flex items-center gap-1.5"
 :class="action.severity === 'danger' ? 'bg-rose-600 text-white' : (action.severity === 'urgent' ? 'bg-amber-600 text-white' : 'bg-primary text-white')"
 >
 {{ action.action_label }}
 <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
 </Link>
 </div>
 </div>`;

const newCode = `<!-- ── 4. ROW 2: "Perlu Tindakan" & "Aksi Cepat" ────────── -->
 <div class="grid grid-cols-1 lg:grid-cols-12 gap-5">

 <!-- Section Perlu Tindakan (7 Cols) -->
 <div class="lg:col-span-7 bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 sm:p-7 flex flex-col justify-between">
 <div>
 <div class="flex items-center justify-between pb-3.5 border-b border-slate-100 mb-4">
 <div class="flex items-center gap-2.5">
 <div class="w-10 h-10 rounded-xl bg-amber-50 border border-amber-200/60 text-amber-600 flex items-center justify-center shadow-sm">
 <svg class="w-5 h-5 stroke-[2]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
 </div>
 <div>
 <h3 class="text-lg font-extrabold text-slate-800">Perlu Tindakan</h3>
 <p class="text-sm text-slate-500 mt-0.5">Peringatan dan hal yang membutuhkan perhatian Anda</p>
 </div>
 </div>
 <span class="text-xs font-bold text-slate-500 bg-slate-100 px-3.5 py-1.5 rounded-full border border-slate-200/60 shadow-sm">
 {{ props.actionRequired.length }} Item
 </span>
 </div>

 <!-- Actionable Alerts List -->
 <div v-if="props.actionRequired.length > 0" class="space-y-3.5 mt-2">
 <div
 v-for="(action, i) in props.actionRequired"
 :key="i"
 class="p-4 rounded-2xl border shadow-sm transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
 :class="action.severity === 'danger' ? 'bg-rose-50/50 border-rose-200/80 text-slate-800' : (action.severity === 'urgent' ? 'bg-amber-50/40 border-amber-200/80 text-slate-800' : 'bg-primary/5 border-primary/20 text-slate-800')"
 >
 <div class="flex items-start gap-3.5 min-w-0">
 <div
 class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0 mt-0.5 font-bold text-sm shadow-sm"
 :class="action.severity === 'danger' ? 'bg-rose-500 text-white shadow-rose-200' : (action.severity === 'urgent' ? 'bg-amber-500 text-white shadow-amber-200' : 'bg-primary text-white shadow-blue-200')"
 >
 {{ i + 1 }}
 </div>
 <div class="min-w-0">
 <div class="flex items-center gap-2.5 flex-wrap">
 <h4 class="text-sm font-extrabold truncate text-slate-700">{{ action.title }}</h4>
 <span 
 class="text-[10px] font-bold uppercase px-2 py-0.5 rounded"
 :class="action.severity === 'danger' ? 'bg-rose-100 text-rose-700' : (action.severity === 'urgent' ? 'bg-amber-100 text-amber-700' : 'bg-blue-100 text-blue-700')"
 >{{ action.badge }}</span>
 </div>
 <p class="text-xs text-slate-500 mt-1.5 leading-relaxed">{{ action.description }}</p>
 </div>
 </div>

 <Link
 :href="action.action_url"
 class="shrink-0 self-start sm:self-center px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm hover:shadow-md transition-all inline-flex items-center gap-1.5"
 :class="action.severity === 'danger' ? 'bg-rose-600 hover:bg-rose-700 text-white shadow-rose-200' : (action.severity === 'urgent' ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-amber-200' : 'bg-primary hover:bg-primary/90 text-white shadow-blue-200')"
 >
 {{ action.action_label }}
 <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
 </Link>
 </div>
 </div>`;

let c2 = c.replace(oldCode, newCode);
if (c === c2) {
    console.log('FAIL REPLACE');
} else {
    fs.writeFileSync('resources/js/Pages/Dashboard.vue', c2);
    console.log('SUCCESS REPLACE');
}
