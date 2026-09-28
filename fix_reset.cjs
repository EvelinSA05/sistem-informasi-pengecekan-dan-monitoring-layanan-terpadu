const fs = require('fs');

function fixFile(filePath) {
    let c = fs.readFileSync(filePath, 'utf8');
    const fixed = c.replace('-1 hover:-2 hover:-2', 'hover:bg-primary/90');
    fs.writeFileSync(filePath, fixed);
}

fixFile('resources/js/Pages/Auth/ResetPassword.vue');
console.log('Fixed ResetPassword.vue');
