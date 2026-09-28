const fs = require('fs');
let c = fs.readFileSync('resources/js/Components/Pagination.vue', 'utf8');

// Fix focus rings
c = c.replace(/focus:ring-primary-500 focus:border-primary-500/g, 'focus:ring-primary focus:border-primary');

// Fix active page link
c = c.replace(/bg-primary-50 text-primary-600/g, 'bg-primary text-white');

fs.writeFileSync('resources/js/Components/Pagination.vue', c);
console.log('Fixed Pagination.vue tailwind classes');
