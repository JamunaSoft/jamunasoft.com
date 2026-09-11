<?php

namespace App\Http\Requests;

use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;

class NewsletterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $turnstileEnabled = (bool) config('services.turnstile.secret_key');

        return [
            'email' => ['required', 'email:rfc', 'max:190'],
            'cf-turnstile-response' => [
                'bail',
                $turnstileEnabled ? 'required' : 'nullable',
                'string',
                new Turnstile($this->ip()),
            ],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'email' => __('Email'),
            'cf-turnstile-response' => __('Security check'),
        ];
    }
}
