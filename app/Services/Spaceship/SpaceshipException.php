<?php

namespace App\Services\Spaceship;

use Illuminate\Http\Client\Response;
use RuntimeException;

class SpaceshipException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|null  $details  Field-level validation errors from the API, if any.
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly ?array $details = null,
    ) {
        parent::__construct($message, $status);
    }

    public static function fromResponse(Response $response): self
    {
        $body = $response->json();

        $message = data_get($body, 'detail')
            ?? data_get($body, 'title')
            ?? 'Spaceship API request failed with HTTP '.$response->status().'.';

        return new self($message, $response->status(), data_get($body, 'data'));
    }

    public static function missingCredentials(): self
    {
        return new self('Spaceship API credentials are not configured. Set SPACESHIP_API_KEY and SPACESHIP_API_SECRET in .env.');
    }
}
