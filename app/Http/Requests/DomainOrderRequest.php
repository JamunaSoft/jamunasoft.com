<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DomainOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $usingExistingContact = $this->user() !== null && $this->input('contact_option') === 'existing';

        return [
            'contact_option' => [
                Rule::requiredIf($this->user() !== null),
                Rule::in(['existing', 'new']),
            ],
            'billing_profile_id' => [
                Rule::requiredIf($usingExistingContact),
                'nullable',
                Rule::exists('billing_profiles', 'id')->where('user_id', $this->user()?->id),
            ],
            'name' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:100'],
            'email' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'domain' => ['required', 'string', 'max:253'],
            'years' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
