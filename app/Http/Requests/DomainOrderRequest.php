<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DomainOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'domain' => ['required', 'string', 'max:253'],
            'years' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
