const fs = require('fs');

let c = fs.readFileSync('resources/js/Pages/Admin/ManajemenUser.vue', 'utf8');

c = c.replace(
    /class="w-8 h-8 flex items-center justify-center rounded-lg text-primary bg-slate-100 sm:bg-transparent sm:text-slate-400 hover:text-primary hover:bg-slate-100 active:scale-95 transition-all"/g,
    'class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-primary hover:bg-slate-100 active:scale-95 transition-all"'
);

c = c.replace(
    /class="w-8 h-8 flex items-center justify-center rounded-lg text-amber-600 bg-amber-50\/90 sm:bg-transparent sm:text-slate-400 hover:text-amber-600 hover:bg-amber-100\/70 active:scale-95 transition-all"/g,
    'class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-amber-600 hover:bg-amber-50 active:scale-95 transition-all"'
);

c = c.replace(
    /class="w-8 h-8 flex items-center justify-center rounded-lg text-rose-600 bg-rose-50\/90 sm:bg-transparent sm:text-slate-400 hover:text-rose-600 hover:bg-rose-100\/70 active:scale-95 transition-all"/g,
    'class="w-8 h-8 flex items-center justify-center rounded-lg text-slate-400 bg-transparent hover:text-rose-600 hover:bg-rose-50 active:scale-95 transition-all"'
);

fs.writeFileSync('resources/js/Pages/Admin/ManajemenUser.vue', c);
console.log('Fixed action buttons colors');
