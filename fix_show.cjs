const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/Periods/Show.vue', 'utf8');

if (!c.includes("import ClientPagination from '@/Components/ClientPagination.vue';")) {
    c = c.replace(
        "import AdminLayout from '@/Layouts/AdminLayout.vue';",
        "import AdminLayout from '@/Layouts/AdminLayout.vue';\nimport ClientPagination from '@/Components/ClientPagination.vue';"
    );
}

// 1. Add pagination states
if (!c.includes("const currentPage = ref(1);")) {
    c = c.replace(
        "const isTableVisible = ref(true);",
        "const isTableVisible = ref(true);\nconst currentPage = ref(1);\nconst perPage = ref(10);"
    );
}

// 2. Add watch to reset page
if (!c.includes("watch([searchQuery, statusFilter], () => {")) {
    c = c.replace(
        "const filteredServices = computed(() => {",
        "watch([searchQuery, statusFilter], () => { currentPage.value = 1; });\n\nconst filteredServices = computed(() => {"
    );
}

// 3. Add paginatedServices and paginationMeta computed properties
if (!c.includes("const paginatedServices = computed(() => {")) {
    c = c.replace(
        "// Helpers\n function getTesterProgress",
        `const paginatedServices = computed(() => {
 const start = (currentPage.value - 1) * perPage.value;
 return filteredServices.value.slice(start, start + perPage.value);
});
const paginationMeta = computed(() => {
 const total = filteredServices.value.length;
 return {
 total: total,
 per_page: perPage.value,
 current_page: currentPage.value,
 last_page: Math.ceil(total / perPage.value) || 1,
 };
});

// Helpers
 function getTesterProgress`
    );
}

// 4. Update template loop
c = c.replace(
    /v-for="srv in filteredServices"/g,
    'v-for="srv in paginatedServices"'
);

// 5. Add ClientPagination component below the table
if (!c.includes("<ClientPagination")) {
    c = c.replace(
        "</tbody>\n </table>\n </div>",
        "</tbody>\n </table>\n </div>\n <ClientPagination :meta=\"paginationMeta\" @update:page=\"currentPage = $event\" @update:perPage=\"perPage = $event; currentPage = 1\" class=\"px-6 py-4 border-t border-slate-100 bg-slate-50/50\" />"
    );
}

fs.writeFileSync('resources/js/Pages/Admin/Periods/Show.vue', c);
console.log('Updated Show.vue with pagination');
