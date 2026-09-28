const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/MasterLayanan.vue', 'utf8');

let c2 = c.replace('<div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col">', '<div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden flex flex-col max-h-[90vh]">');
console.log(c === c2 ? 'FAILED TO REPLACE CONTAINER' : 'REPLACED CONTAINER');
c = c2;

c2 = c.replace('<div class="p-6">', '<div class="p-6 overflow-y-auto">');
console.log(c === c2 ? 'FAILED TO REPLACE P-6' : 'REPLACED P-6');

fs.writeFileSync('resources/js/Pages/Admin/MasterLayanan.vue', c2);
console.log('Fixed scrolling in modal');
