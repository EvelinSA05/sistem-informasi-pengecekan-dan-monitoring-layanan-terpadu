const fs = require('fs');

function fixPos(filePath) {
    let c = fs.readFileSync(filePath, 'utf8');
    c = c.replace('<div class="mt-6 pt-5 border-t border-slate-100 text-center">', '<div class="mt-3 text-center">');
    fs.writeFileSync(filePath, c);
}

fixPos('resources/js/Pages/Auth/ForgotPassword.vue');
fixPos('resources/js/Pages/Auth/ResetPassword.vue');
console.log('Fixed position');
