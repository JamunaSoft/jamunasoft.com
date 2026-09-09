<?php

namespace App\Jobs;

use App\Enums\DomainOrderStatus;
use App\Models\DomainOrder;
use App\Services\DomainOrderService;
use App\Services\Spaceship\SpaceshipClient;
use App\Services\Spaceship\SpaceshipException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PollDomainTransfer implements ShouldQueue
{
    use Queueable;

    // Transfers may take several days. Check every five minutes for seven days.
    public int $tries = 2016;

    public int $backoff = 300;

    public function __construct(public DomainOrder $order) {}

    public function handle(DomainOrderService $service, SpaceshipClient $client): void
    {
        $this->order->refresh();
        if ($this->order->status !== DomainOrderStatus::Processing) {
            return;
        }

        try {
            if ($this->order->spaceship_operation_id && ! data_get($this->order->meta, 'transfer_started_notified_at')) {
                $operation = $client->getAsyncOperation($this->order->spaceship_operation_id);
                if (in_array(strtolower((string) data_get($operation, 'status')), ['failed', 'error', 'cancelled'], true)) {
                    $service->fail($this->order, 'Transfer request failed: '.json_encode(data_get($operation, 'details')));

                    return;
                }
            }
            $transfer = $client->getTransfer($this->order->domain_name);
            $status = strtolower((string) data_get($transfer, 'status'));
            $this->order->update(['error_message' => null]);

            if (in_array($status, ['failed', 'cancelled', 'canceled', 'rejected'], true)) {
                $service->fail($this->order, 'Domain transfer '.$status.'. Check Spaceship before retrying.');

                return;
            }

            if (data_get($transfer, 'direction') === 'in') {
                $service->notifyTransferStarted($this->order);
            }

            // An accepted async request is not a completed registry transfer.
            if (data_get($transfer, 'direction') === 'in'
                && data_get($transfer, 'finishedAt')
                && in_array($status, ['success', 'succeeded', 'completed'], true)) {
                $service->complete($this->order);

                return;
            }
        } catch (SpaceshipException $e) {
            // A failed status lookup must never enable another paid transfer.
            $this->order->update(['error_message' => 'Transfer status check will be retried: '.$e->getMessage()]);
        }

        $this->release($this->backoff);
    }

    public function failed(?\Throwable $exception): void
    {
        $this->order->refresh();
        if ($this->order->status === DomainOrderStatus::Processing) {
            $this->order->update(['error_message' => 'Transfer monitoring stopped. Check the transfer in Spaceship; do not submit another transfer.']);
        }
    }
}
