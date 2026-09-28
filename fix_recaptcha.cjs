const fs = require('fs');

let c = fs.readFileSync('resources/js/Pages/Auth/Login.vue', 'utf8');

const oldCaptchaDiv = `<div class="g-recaptcha" :data-sitekey="siteKey"></div>`;
const newCaptchaDiv = `<div id="recaptcha-container" class="g-recaptcha" :data-sitekey="siteKey"></div>`;

const oldOnMounted = `onMounted(() => {
    if (!document.getElementById('recaptcha-script')) {
        const script = document.createElement('script');
        script.id = 'recaptcha-script';
        script.src = 'https://www.google.com/recaptcha/api.js';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    }
});`;

const newOnMounted = `onMounted(() => {
    if (!siteKey) return;

    const renderRecaptcha = () => {
        if (window.grecaptcha && window.grecaptcha.render) {
            const container = document.getElementById('recaptcha-container');
            if (container && container.innerHTML === '') {
                window.grecaptcha.render('recaptcha-container', {
                    'sitekey': siteKey
                });
            }
        }
    };

    if (!document.getElementById('recaptcha-script')) {
        window.onRecaptchaLoadCallback = renderRecaptcha;
        const script = document.createElement('script');
        script.id = 'recaptcha-script';
        script.src = 'https://www.google.com/recaptcha/api.js?onload=onRecaptchaLoadCallback&render=explicit';
        script.async = true;
        script.defer = true;
        document.head.appendChild(script);
    } else {
        setTimeout(() => {
            if (window.grecaptcha && window.grecaptcha.ready) {
                window.grecaptcha.ready(renderRecaptcha);
            } else {
                renderRecaptcha();
            }
        }, 100);
    }
});`;

c = c.replace(oldCaptchaDiv, newCaptchaDiv);
c = c.replace(oldOnMounted, newOnMounted);

fs.writeFileSync('resources/js/Pages/Auth/Login.vue', c);
console.log('Fixed recaptcha logic');
