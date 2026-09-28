const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

const oldCode = `<!-- ── Panduan Pengujian Layanan & Akses Layanan Ditugaskan (Dari main) ── -->
 <div class="bg-white rounded-3xl border border-slate-200/80 shadow-xs p-6 sm:p-7 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
 <div class="flex items-start gap-4">
 <div class="w-10 h-10 rounded-2xl bg-primary border border-primary text-primary flex items-center justify-center shrink-0 shadow-xs mt-0.5">
 <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-base font-bold text-slate-800">Panduan Pengujian Layanan</h3>
 <p class="text-xs sm:text-sm text-slate-600 mt-1 leading-relaxed max-w-3xl">
 Pastikan untuk memeriksa tautan (URL) dari setiap layanan yang ditugaskan kepada Anda. Catat dan laporkan kendala yang Anda temukan secara jelas pada kolom deskripsi. Jika tidak ada kendala, simpan dengan status <strong class="text-emerald-700 font-bold">"NORMAL"</strong>. Jika terdapat instruksi khusus pada layanan tersebut, mohon perhatikan dan laksanakan instruksi tersebut.
 </p>
 </div>
 </div>
 <Link
 :href="route('tester.services.index')"
 class="shrink-0 inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-white text-xs font-bold shadow-md shadow-primary/20 transition-all group cursor-pointer"
 >
 <span>Buka Layanan Ditugaskan</span>
 <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
 </svg>
 </Link>
 </div>`;

const newCode = `<!-- ── Panduan Pengujian Layanan & Akses Layanan Ditugaskan ── -->
 <div class="bg-gradient-to-r from-slate-50 to-white rounded-2xl border border-slate-200/60 shadow-sm p-6 sm:p-7 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-6">
 <div class="flex items-start gap-4">
 <div class="w-12 h-12 rounded-xl bg-primary/10 border border-primary/20 text-primary flex items-center justify-center shrink-0 mt-0.5">
 <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
 </svg>
 </div>
 <div>
 <h3 class="text-lg font-extrabold text-slate-800">Panduan Pengujian Layanan</h3>
 <p class="text-sm text-slate-500 mt-1.5 leading-relaxed max-w-3xl">
 Pastikan untuk memeriksa tautan (URL) dari setiap layanan yang ditugaskan kepada Anda. Catat dan laporkan kendala yang Anda temukan secara jelas pada kolom deskripsi. Jika tidak ada kendala, simpan dengan status <strong class="text-emerald-600 font-bold bg-emerald-50 px-1.5 py-0.5 rounded">"NORMAL"</strong>. Jika terdapat instruksi khusus, mohon perhatikan dan laksanakan instruksi tersebut.
 </p>
 </div>
 </div>
 <Link
 :href="route('tester.services.index')"
 class="shrink-0 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-primary text-white text-sm font-bold shadow-md shadow-primary/25 hover:bg-primary/90 hover:shadow-lg hover:shadow-primary/30 transition-all group cursor-pointer"
 >
 <span>Buka Layanan Ditugaskan</span>
 <svg class="w-4 h-4 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
 <path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
 </svg>
 </Link>
 </div>`;

let c2 = c.replace(oldCode, newCode);
if (c === c2) {
    console.log('FAIL REPLACE');
} else {
    fs.writeFileSync('resources/js/Pages/Dashboard.vue', c2);
    console.log('SUCCESS REPLACE');
}
