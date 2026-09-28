const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/Periods/Form.vue', 'utf8');

c = c.replace(
    'class="bg-primary/70 border border-primary/80 rounded-2xl p-4 flex items-center gap-3 text-xs text-primary"',
    'class="bg-primary/10 border border-primary/20 rounded-2xl p-4 flex items-center gap-3 text-xs text-primary"'
);

c = c.replace(
    'class="w-8 h-8 rounded-xl bg-primary/80 text-primary flex items-center justify-center shrink-0"',
    'class="w-8 h-8 rounded-xl bg-primary text-white flex items-center justify-center shrink-0"'
);

fs.writeFileSync('resources/js/Pages/Admin/Periods/Form.vue', c);
console.log('Replaced banner');
