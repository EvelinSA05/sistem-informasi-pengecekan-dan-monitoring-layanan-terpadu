<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class Recaptcha implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        \Illuminate\Support\Facades\Log::info('reCAPTCHA Token received: ' . substr($value, 0, 30) . '...');

        $response = \Illuminate\Support\Facades\Http::withoutVerifying()->asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('services.recaptcha.secret'),
            'response' => $value,
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            \Illuminate\Support\Facades\Log::error('reCAPTCHA verification failed: ' . $response->body());
            $fail('Verifikasi Captcha gagal. Silakan coba lagi.');
        }
    }
}
