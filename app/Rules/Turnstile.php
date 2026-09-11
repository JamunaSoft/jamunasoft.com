<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class Turnstile implements ValidationRule
{
    public function __construct(private readonly ?string $remoteIp = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $secret = config('services.turnstile.secret_key');

        // Widget is disabled when no secret is configured (e.g. local dev).
        if (! $secret) {
            return;
        }

        try {
            $response = Http::asForm()->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => $secret,
                'response' => $value,
                'remoteip' => $this->remoteIp,
            ]);

            if ($response->successful() && $response->json('success') === true) {
                return;
            }
        } catch (Throwable $e) {
            Log::warning('Turnstile verification request failed: '.$e->getMessage());
        }

        $fail(__('Security check failed. Please try again.'));
    }
}
