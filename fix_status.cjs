const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Admin/ManajemenUser.vue', 'utf8');

c = c.replace(
    /'border-primary bg-primary'/g,
    "'border-primary bg-primary/10'"
);

fs.writeFileSync('resources/js/Pages/Admin/ManajemenUser.vue', c);
console.log('Fixed button bg');
