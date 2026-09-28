const fs = require('fs');
let c = fs.readFileSync('resources/js/Pages/Auth/ForgotPassword.vue', 'utf8');

const oldCode = `<button
 type="submit"
 :disabled="form.processing"
 class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm text-white bg-primary -1 hover:-2 hover:-2 shadow-md shadow-primary/25 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.99] transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
 >`;

const newCode = `<button
 type="submit"
 :disabled="form.processing"
 class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl font-bold text-sm text-white bg-primary hover:bg-primary/90 shadow-md shadow-primary/25 hover:shadow-lg hover:shadow-primary/30 active:scale-[0.99] transition-all duration-150 disabled:opacity-60 disabled:cursor-not-allowed"
 >`;

let c2 = c.replace(oldCode, newCode);
if (c === c2) {
    console.log('FAIL REPLACE button class');
} else {
    fs.writeFileSync('resources/js/Pages/Auth/ForgotPassword.vue', c2);
    console.log('SUCCESS REPLACE button class');
}
