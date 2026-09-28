const fs = require('fs');

let c = fs.readFileSync('resources/js/Pages/Dashboard.vue', 'utf8');
c = c.replace("'z-10 bg-primary text-primary font-bold'", "'z-10 bg-primary text-white font-bold'");
fs.writeFileSync('resources/js/Pages/Dashboard.vue', c);
console.log('Fixed dashboard pagination color');
