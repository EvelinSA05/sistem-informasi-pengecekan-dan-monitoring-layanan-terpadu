const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');

let c2 = c.replace('<div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-primary flex-wrap">', '<div class="mt-2.5 flex items-center sm:justify-end gap-2 text-xs font-semibold text-white flex-wrap">');

if (c === c2) {
    console.log("FAILED TO REPLACE TEXT COLOR");
} else {
    fs.writeFileSync('resources/js/Pages/Dashboard.vue', c2);
    console.log("REPLACED TEXT COLOR");
}
