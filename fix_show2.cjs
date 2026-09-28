const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/Periods/Show.vue', 'utf8');

const target = "// Helpers\r\nfunction getTesterProgress";
const target2 = "// Helpers\nfunction getTesterProgress";

const replacement = `const paginatedServices = computed(() => {
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
function getTesterProgress`;

if (c.includes(target)) {
    c = c.replace(target, replacement);
} else if (c.includes(target2)) {
    c = c.replace(target2, replacement);
} else {
    console.log("COULD NOT FIND TARGET!");
}

fs.writeFileSync('resources/js/Pages/Admin/Periods/Show.vue', c);
console.log('Fixed Show.vue');
