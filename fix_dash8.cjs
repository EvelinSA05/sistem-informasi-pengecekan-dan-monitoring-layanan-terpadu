const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Tester/Services/Index.vue', 'utf8');

// 1. Add import Pagination
if (!c.includes("import Pagination from")) {
    c = c.replace("import AdminLayout from '@/Layouts/AdminLayout.vue';", "import AdminLayout from '@/Layouts/AdminLayout.vue';\nimport Pagination from '@/Components/Pagination.vue';");
}

// 2. Replace Pagination Block
const oldPagination = `<!-- Pagination Component -->
 <div
 v-if="services.links && services.links.length > 3"
 class="px-6 py-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4"
 >
 <div>
 <p class="text-xs sm:text-sm text-slate-600">
 Menampilkan <span class="font-bold text-slate-800">{{ services.from || 0 }}</span> sampai <span class="font-bold text-slate-800">{{ services.to || 0 }}</span> dari <span class="font-bold text-slate-800">{{ services.total || 0 }}</span> data
 </p>
 </div>
 <div>
 <nav class="relative z-0 inline-flex shadow-xs rounded-xl overflow-hidden border border-slate-200 bg-white" aria-label="Pagination">
 <template v-for="(link, i) in services.links" :key="i">
 <Link
 v-if="link.url"
 :href="link.url"
 v-html="link.label.replace('pagination.previous', '&laquo; Previous').replace('pagination.next', 'Next &raquo;')"
 preserve-state
 preserve-scroll
 :class="[
 'relative inline-flex items-center px-3.5 py-1.5 border-r border-slate-200 last:border-r-0 text-xs font-semibold transition-colors',
 link.active ? 'z-10 bg-primary text-primary font-bold' : 'text-slate-600 hover:bg-slate-50 hover:text-primary',
 ]"
 />
 <span
 v-else
 v-html="link.label.replace('pagination.previous', '&laquo; Previous').replace('pagination.next', 'Next &raquo;')"
 class="relative inline-flex items-center px-3.5 py-1.5 border-r border-slate-200 last:border-r-0 bg-slate-50 text-xs font-medium text-slate-300"
 ></span>
 </template>
 </nav>
 </div>
 </div>`;

const newPagination = `<!-- Pagination Component -->
 <div v-if="services.links && services.links.length > 3" class="px-6 py-4 border-t border-slate-200 bg-slate-50/50">
 <Pagination :meta="services" />
 </div>`;

let c2 = c.replace(oldPagination, newPagination);
if (c === c2) {
    console.log('FAIL REPLACE');
} else {
    fs.writeFileSync('resources/js/Pages/Tester/Services/Index.vue', c2);
    console.log('SUCCESS REPLACE');
}
