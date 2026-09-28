const fs = require('fs');

function fixLink(filePath) {
    let c = fs.readFileSync(filePath, 'utf8');
    const oldLink = `class="inline-flex items-center gap-2 text-xs sm:text-sm font-semibold text-slate-600 hover:text-primary transition-colors"`;
    const newLink = `class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 rounded-xl border border-slate-200 bg-slate-50/80 text-xs sm:text-sm font-bold text-slate-600 hover:bg-slate-100 hover:text-primary hover:border-slate-300 transition-all duration-150"`;
    c = c.replace(oldLink, newLink);
    fs.writeFileSync(filePath, c);
}

fixLink('resources/js/Pages/Auth/ForgotPassword.vue');
// check if ResetPassword has it too
let reset = fs.readFileSync('resources/js/Pages/Auth/ResetPassword.vue', 'utf8');
if(reset.includes('Kembali ke Halaman Masuk')) {
   // It might not have it.
}

console.log('Fixed link to look like a button');
