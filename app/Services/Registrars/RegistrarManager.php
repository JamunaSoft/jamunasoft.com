<?php

namespace App\Services\Registrars;

class RegistrarManager
{
    /** All selectable registrar backends, key => label. */
    public const PROVIDERS = [
        'spaceship' => 'Spaceship',
        'resellcube' => 'ResellCube',
    ];

    /**
     * The registrar new registrations and availability searches go through,
     * as selected in Website Settings. Renewals always use the registrar
     * that holds the domain, not this setting.
     */
    public function activeKey(): string
    {
        $key = (string) settings('active_domain_registrar', 'spaceship');

        return array_key_exists($key, self::PROVIDERS) ? $key : 'spaceship';
    }

    public function active(): Registrar
    {
        return $this->for($this->activeKey());
    }

    public function for(?string $key): Registrar
    {
        return match ($key ?: 'spaceship') {
            'resellcube' => app(ResellCubeRegistrar::class),
            default => app(SpaceshipRegistrar::class),
        };
    }
}
