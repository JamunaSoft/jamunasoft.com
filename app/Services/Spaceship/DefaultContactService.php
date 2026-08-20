<?php

namespace App\Services\Spaceship;

use App\Support\Settings;

/**
 * Resolves the default registrant contact used for every registration made
 * through the panel (the white-label proxy contact). The Spaceship contact id
 * is cached in settings alongside a hash of the source fields, so editing the
 * contact details in Website Settings transparently creates a fresh contact.
 */
class DefaultContactService
{
    public function __construct(protected SpaceshipClient $client) {}

    public function contactId(): string
    {
        $attributes = $this->attributes();
        $hash = md5(json_encode($attributes));

        $cachedId = (string) settings('spaceship_default_contact_id', '');
        $cachedHash = (string) settings('spaceship_default_contact_hash', '');

        if ($cachedId !== '' && $cachedHash === $hash) {
            return $cachedId;
        }

        $contactId = $this->client->saveContact($attributes);

        Settings::set([
            'spaceship_default_contact_id' => $contactId,
            'spaceship_default_contact_hash' => $hash,
        ], 'domains');

        return $contactId;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [
            'firstName' => (string) settings('domain_registrant_first_name', ''),
            'lastName' => (string) settings('domain_registrant_last_name', ''),
            'organization' => (string) settings('domain_registrant_org', ''),
            'email' => (string) settings('domain_registrant_email', ''),
            'phone' => (string) settings('domain_registrant_phone', ''),
            'address1' => (string) settings('domain_registrant_address', ''),
            'city' => (string) settings('domain_registrant_city', ''),
            'postalCode' => (string) settings('domain_registrant_postal', ''),
            'country' => (string) settings('domain_registrant_country', 'BD'),
        ];

        $required = ['firstName', 'lastName', 'email', 'phone', 'address1', 'city', 'country'];

        foreach ($required as $field) {
            if ($attributes[$field] === '') {
                throw new SpaceshipException(
                    "Default registrant contact is incomplete: '{$field}' is missing. Fill in the Domains tab of Website Settings."
                );
            }
        }

        return array_filter($attributes, fn (string $value) => $value !== '');
    }
}
