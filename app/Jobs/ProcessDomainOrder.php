<?php

namespace App\Jobs;

use App\Enums\DomainOrderStatus;
use App\Models\DomainOrder;
use App\Services\DomainOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessDomainOrder implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public DomainOrder $order) {}

    public function handle(DomainOrderService $service): void
    {
        $this->order->refresh();

        // Only paid orders may be processed; guards against double dispatch.
        if (! in_array($this->order->status, [DomainOrderStatus::Paid, DomainOrderStatus::Failed], true)) {
            return;
        }

        $service->process($this->order);
    }
}
