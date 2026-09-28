const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/MasterLayanan.vue', 'utf8');

if (!c.includes("import Pagination from '@/Components/Pagination.vue';")) {
    c = c.replace(
        "import AdminLayout from '@/Layouts/AdminLayout.vue';",
        "import AdminLayout from '@/Layouts/AdminLayout.vue';\nimport Pagination from '@/Components/Pagination.vue';"
    );
}

const startComment = '<!-- ── Pagination ────────────────────────────────────── -->';
const endDiv = '</div>\n\n <!-- ── Modal Wrapper Utility ───────────────────────────── -->';

if (c.includes(startComment) && c.includes(endDiv)) {
    const startIndex = c.indexOf(startComment);
    const endIndex = c.indexOf(endDiv);
    
    const before = c.substring(0, startIndex);
    const after = c.substring(endIndex);
    
    const newPagination = `<!-- ── Pagination ────────────────────────────────────── -->\n <Pagination :meta="services" class="px-6 py-2 border-t border-slate-200 bg-slate-50/50" />\n`;
    
    c = before + newPagination + after;
}

fs.writeFileSync('resources/js/Pages/Admin/MasterLayanan.vue', c);
console.log('Replaced pagination');
