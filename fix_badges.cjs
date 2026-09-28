const fs = require('fs');

function fixBadge(filePath) {
    let c = fs.readFileSync(filePath, 'utf8');
    // For ForgotPassword and ResetPassword
    c = c.replace(
        '<span class="w-2 h-2 rounded-full bg-primary animate-pulse"></span>',
        '<span class="w-2 h-2 rounded-full bg-white animate-pulse shadow-sm shadow-white/50"></span>'
    );
    c = c.replace(
        '<span class="text-[10px] font-bold tracking-wider text-primary uppercase">CHECKER SYSTEM</span>',
        '<span class="text-[10px] font-bold tracking-wider text-white uppercase">CHECKER SYSTEM</span>'
    );
    fs.writeFileSync(filePath, c);
}

fixBadge('resources/js/Pages/Auth/ForgotPassword.vue');
fixBadge('resources/js/Pages/Auth/ResetPassword.vue');
console.log('Fixed badges');
