<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'company' => ['nullable', 'string', 'max:190'],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'subject' => ['nullable', 'string', 'max:190'],
            'message' => ['required', 'string', 'max:5000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('Full Name'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'company' => __('Company'),
            'service_id' => __('Service'),
            'subject' => __('Subject'),
            'message' => __('Message'),
            'attachment' => __('Attachment'),
            'consent' => __('Consent'),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'consent.accepted' => __('Please agree to be contacted so we can respond to your message.'),
        ];
    }
}
