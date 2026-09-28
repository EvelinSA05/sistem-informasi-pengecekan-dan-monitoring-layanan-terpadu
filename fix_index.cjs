const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/Periods/Index.vue', 'utf8');

c = c.replace(
    '<p class="font-semibold text-slate-800 leading-tight group-hover:text-primary">\n  {{ period.period_title }}\n  </p>\n  <p class="text-xs text-slate-400 mt-0.5 font-normal">\n  {{ period.created_at_label }}\n  </p>',
    `<div class="space-y-1">
  <p class="font-semibold text-slate-800 leading-tight group-hover:text-primary">
  {{ period.period_title }}
  </p>
  <p class="text-[11px] text-slate-400 font-normal">
  {{ period.created_at_label }}
  </p>
  <div v-if="period.deadline_date_formatted || period.deadline_date" class="flex items-center gap-1.5 mt-1">
  <span class="px-1.5 py-0.5 rounded-md bg-rose-50 text-rose-600 font-semibold text-[10px] border border-rose-100 uppercase tracking-wide">Deadline</span>
  <span class="text-[11px] text-slate-500 font-medium">{{ period.deadline_date_formatted || period.deadline_date }}</span>
  </div>
  </div>`
);

c = c.replace(
    '<path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H8v-2.414A2 2 0 018.586 12z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>'
);

fs.writeFileSync('resources/js/Pages/Admin/Periods/Index.vue', c);
console.log('Replaced successfully');
