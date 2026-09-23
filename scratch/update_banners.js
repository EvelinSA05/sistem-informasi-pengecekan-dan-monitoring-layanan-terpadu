const fs = require('fs');
const path = require('path');

const rootDir = 'c:/xampp/htdocs/majadigi-checking-layanan';

function updateFile(relativePath, replacements) {
    const filePath = path.join(rootDir, relativePath);
    if (!fs.existsSync(filePath)) {
        console.log('File not found:', filePath);
        return;
    }
    let content = fs.readFileSync(filePath, 'utf8');
    let changed = false;
    for (const { search, replace } of replacements) {
        if (content.includes(search)) {
            content = content.replace(search, replace);
            changed = true;
        } else {
            console.log('Search pattern not found in', relativePath, ':', search);
        }
    }
    if (changed) {
        fs.writeFileSync(filePath, content, 'utf8');
        console.log('Updated:', relativePath);
    }
}

// 1. Dashboard.vue
updateFile('resources/js/Pages/Dashboard.vue', [
    {
        search: `<div class="absolute inset-0 opacity-50" style="background-image: url('/images/banner_1.jpg'); background-size: cover; mix-blend-mode: overlay; background-position: center;"></div>`,
        replace: `<div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_1.jpg');"></div>\n  <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>`
    },
    {
        search: `<div class="absolute inset-0 opacity-50" style="background-image: url('/images/banner_2.jpg'); background-size: cover; mix-blend-mode: overlay; background-position: center;"></div>`,
        replace: `<div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_2.jpg');"></div>\n  <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>`
    }
]);

// 2. Admin/Periods/Show.vue
updateFile('resources/js/Pages/Admin/Periods/Show.vue', [
    {
        search: `<div class="absolute inset-0 opacity-[0.40] mix-blend-overlay" style="background-image: url('/images/banner_4.jpg'); background-size: cover; background-position: center;"></div>`,
        replace: `<div class="absolute inset-0 opacity-70 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>\n  <div class="absolute inset-0 bg-gradient-to-r from-[#1e3a8a]/85 via-[#1e3a8a]/45 to-transparent pointer-events-none"></div>`
    }
]);

// 3. Auth/Login.vue
updateFile('resources/js/Pages/Auth/Login.vue', [
    {
        search: `<div class="absolute inset-0 opacity-[0.40]" style="background-image: url('/images/banner_3.jpg'); background-size: cover; background-position: center; mix-blend-mode: overlay; background-position: center;"></div>`,
        replace: `<div class="absolute inset-0 opacity-60 bg-cover bg-center" style="background-image: url('/images/banner_3.jpg');"></div>\n  <div class="absolute inset-0 bg-gradient-to-b from-[#1e3a8a]/70 via-[#1e3a8a]/50 to-[#1e3a8a]/85 pointer-events-none"></div>`
    }
]);

// 4. Auth/Register.vue
updateFile('resources/js/Pages/Auth/Register.vue', [
    {
        search: `<div class="absolute inset-0 opacity-[0.40]" style="background-image: url('/images/banner_3.jpg'); background-size: cover; background-position: center; mix-blend-mode: overlay; background-position: center;"></div>`,
        replace: `<div class="absolute inset-0 opacity-60 bg-cover bg-center" style="background-image: url('/images/banner_4.jpg');"></div>\n  <div class="absolute inset-0 bg-gradient-to-b from-[#1e3a8a]/70 via-[#1e3a8a]/50 to-[#1e3a8a]/85 pointer-events-none"></div>`
    }
]);

console.log('Script finish execution');
