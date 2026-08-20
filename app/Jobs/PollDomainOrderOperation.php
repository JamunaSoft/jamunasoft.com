<?php

namespace App\Jobs;

use App\Enums\DomainOrderStatus;
use App\Models\DomainOrder;
use App\Services\DomainOrderService;
use App\Services\Spaceship\SpaceshipClient;
use App\Services\Spaceship\SpaceshipException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Polls the Spaceship async operation behind an order until it settles.
 * Registration normally completes within a minute; we poll every 20 seconds
 * and give up after ~10 minutes, marking the order for manual review.
 */
class PollDomainOrderOperation implements ShouldQueue
{
    use Queueable;

    public int $tries = 30;

    /** @var int seconds between poll attempts */
    public int $backoff = 20;

    public function __construct(public DomainOrder $order) {}

    public function handle(DomainOrderService $service, SpaceshipClient $client): void
    {
        $this->order->refresh();

        if ($this->order->status !== DomainOrderStatus::Processing) {
            return;
        }

        // No operation id means the API answered synchronously — verify by sync.
        if (! $this->order->spaceship_operation_id) {
            $service->complete($this->order);

            return;
        }

        try {
            $operation = $client->getAsyncOperation($this->order->spaceship_operation_id);
        } catch (SpaceshipException $e) {
            $service->fail($this->order, 'Could not check operation status: '.$e->getMessage());

            return;
        }

        $status = strtolower((string) data_get($operation, 'status'));

        if (in_array($status, ['success', 'succeeded', 'completed'], true)) {
            $service->complete($this->order);

            return;
        }

        if (in_array($status, ['failed', 'error', 'cancelled'], true)) {
            $details = data_get($operation, 'details');
            $service->fail($this->order, is_array($details)
                ? (string) json_encode($details)
                : (string) ($details ?? 'Registrar operation failed.'));

            return;
        }

        $this->release($this->backoff);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->order->refresh();

        if ($this->order->status === DomainOrderStatus::Processing) {
            app(DomainOrderService::class)->fail(
                $this->order,
                'Operation did not settle in time: '.($exception?->getMessage() ?? 'poll attempts exhausted').' Check Spaceship manually before retrying.',
            );
        }
    }
}
