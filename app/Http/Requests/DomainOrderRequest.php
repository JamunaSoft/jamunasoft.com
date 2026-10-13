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
        if ($this->filled('website_url_hp')) {
            return [];
        }

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
            'name' => ['nullable', 'string', 'max:100'],
            'first_name' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:125'],
            'last_name' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:125'],
            'email' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'email', 'max:255'],
            'phone' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'regex:/^\+\d{1,3}\.\d{4,12}$/', 'max:17'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'address' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:64'],
            'address2' => ['nullable', 'string', 'max:64'],
            'city' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:64'],
            'state' => ['nullable', 'string', 'max:64'],
            'postal_code' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'string', 'max:16'],
            'country' => [Rule::requiredIf(! $usingExistingContact), 'nullable', 'regex:/^[A-Za-z]{2}$/'],
            'domain' => ['required', 'string', 'max:253'],
            'years' => ['required', 'integer', 'min:1', 'max:5'],
        ];
    }
}
