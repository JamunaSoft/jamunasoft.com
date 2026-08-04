<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuotationRequest extends FormRequest
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
            'company' => ['nullable', 'string', 'max:190'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['required', 'email:rfc', 'max:190'],
            'preferred_contact' => ['required', Rule::in(array_keys(self::preferredContactOptions()))],
            'service_id' => ['nullable', 'integer', 'exists:services,id'],
            'project_type' => ['nullable', 'string', 'max:190'],
            'existing_url' => ['nullable', 'url', 'max:190'],
            'budget' => ['nullable', Rule::in(self::budgetOptions())],
            'timeline' => ['nullable', Rule::in(self::timelineOptions())],
            'message' => ['required', 'string', 'max:8000'],
            'required_features' => ['nullable', 'array'],
            'required_features.*' => ['string', Rule::in(self::featureOptions())],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:5120'],
            'referral_source' => ['nullable', Rule::in(self::referralSourceOptions())],
            'consent' => ['accepted'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => __('Full Name'),
            'company' => __('Company'),
            'phone' => __('Phone'),
            'email' => __('Email'),
            'preferred_contact' => __('Preferred Contact Method'),
            'service_id' => __('Service'),
            'project_type' => __('Project Type'),
            'existing_url' => __('Existing Website URL'),
            'budget' => __('Estimated Budget'),
            'timeline' => __('Expected Timeline'),
            'message' => __('Project Description'),
            'required_features' => __('Required Features'),
            'attachment' => __('Attachment'),
            'referral_source' => __('How did you hear about us?'),
            'consent' => __('Consent'),
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'consent.accepted' => __('Please agree to be contacted so we can prepare your quotation.'),
        ];
    }

    /** @return array<string, string> value => label */
    public static function preferredContactOptions(): array
    {
        return [
            'phone' => __('Phone Call'),
            'email' => __('Email'),
            'whatsapp' => __('WhatsApp'),
        ];
    }

    /** @return list<string> */
    public static function budgetOptions(): array
    {
        return [
            'Under ৳25,000',
            '৳25,000 – ৳50,000',
            '৳50,000 – ৳1,00,000',
            '৳1 – 3 Lakh',
            '৳3 Lakh+',
            'Not sure yet',
        ];
    }

    /** @return list<string> */
    public static function timelineOptions(): array
    {
        return [
            'Urgent (within 2 weeks)',
            '2 – 4 weeks',
            '1 – 2 months',
            '2 – 6 months',
            'Flexible / not decided',
        ];
    }

    /** @return list<string> */
    public static function featureOptions(): array
    {
        return [
            'Online payments',
            'Admin dashboard',
            'Mobile app',
            'Multi-language',
            'Inventory management',
            'Delivery integration',
            'SMS/Email notifications',
            'Reporting & analytics',
        ];
    }

    /** @return list<string> */
    public static function referralSourceOptions(): array
    {
        return [
            'Google Search',
            'Facebook',
            'LinkedIn',
            'YouTube',
            'Referred by a friend or colleague',
            'Existing client',
            'Other',
        ];
    }
}
