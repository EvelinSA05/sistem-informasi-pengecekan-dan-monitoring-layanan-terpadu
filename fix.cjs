const fs = require('fs');
let content = fs.readFileSync('resources/js/Pages/Admin/Periods/Form.vue', 'utf8');

content = content.replace('<div class="flex items-center gap-3.5">', '<div class="flex items-center gap-3.5 min-w-0 flex-1 pr-2">');

content = content.replace(
    '<!-- Name & Email -->\n <div>\n <p class="text-sm font-bold text-slate-800 leading-tight">', 
    '<!-- Name & Email -->\n <div class="min-w-0">\n <p class="text-sm font-bold text-slate-800 leading-tight truncate">'
);

content = content.replace(
    '<p class="text-xs text-slate-400 mt-0.5 font-medium">', 
    '<p class="text-xs text-slate-400 mt-0.5 font-medium truncate">'
);

content = content.replace(
    '<!-- Right side badge -->\n <div v-if="isTesterSelected(tester.id)">', 
    '<!-- Right side badge -->\n <div v-if="isTesterSelected(tester.id)" class="shrink-0">'
);

fs.writeFileSync('resources/js/Pages/Admin/Periods/Form.vue', content);
console.log('Replaced layout classes');
