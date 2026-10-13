<?php

namespace App\Support;

use App\Models\DomainOrder;
use App\Services\Registrars\RegistrarException;
use Illuminate\Support\Facades\Validator;

class RegistrantContact
{
    public static function fromCustomer(array $customer): array
    {
        $name = preg_split('/\s+/', trim((string) ($customer['name'] ?? '')), 2);
        $fields = [
            'firstName' => $customer['first_name'] ?? ($name[0] ?? ''),
            'lastName' => $customer['last_name'] ?? ($name[1] ?? ''),
            'organization' => $customer['company_name'] ?? '',
            'email' => $customer['email'] ?? '',
            'phone' => $customer['phone'] ?? '',
            'address1' => $customer['address'] ?? '',
            'address2' => $customer['address2'] ?? '',
            'city' => $customer['city'] ?? '',
            'stateProvince' => $customer['state'] ?? '',
            'postalCode' => $customer['postal_code'] ?? '',
            'country' => strtoupper((string) ($customer['country'] ?? '')),
        ];

        return array_map(fn ($value) => trim((string) $value), $fields);
    }

    public static function forOrder(DomainOrder $order): array
    {
        $customer = array_merge($order->user?->only(['company_name', 'address', 'city', 'postal_code', 'country']) ?? [], [
            'name' => $order->customer_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone,
        ]);

        return array_merge(self::fromCustomer($customer), data_get($order->meta, 'registrant', []));
    }

    public static function validate(array $attributes): array
    {
        $validator = Validator::make($attributes, [
            'firstName' => ['required', 'string', 'max:125'],
            'lastName' => ['required', 'string', 'max:125'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'regex:/^\+\d{1,3}\.\d{4,12}$/', 'max:17'],
            'address1' => ['required', 'string', 'max:64'],
            'address2' => ['nullable', 'string', 'max:64'],
            'city' => ['required', 'string', 'max:64'],
            'stateProvince' => ['nullable', 'string', 'max:64'],
            'postalCode' => ['required', 'string', 'max:16'],
            'country' => ['required', 'regex:/^[A-Z]{2}$/'],
            'organization' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            throw new RegistrarException('Customer registrant contact is incomplete or invalid: '.$validator->errors()->first().' Edit the order’s customer contact before retrying. Phone format: +880.1700000000.');
        }

        return array_filter($validator->validated(), fn ($value) => $value !== '' && $value !== null);
    }
}
