<?php

namespace App\Filament\Resources\DomainOrderResource\Pages;

use App\Enums\DomainOrderType;
use App\Filament\Resources\DomainOrderResource;
use App\Models\DomainOrder;
use App\Services\DomainOrderService;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Exceptions\Halt;

class ListDomainOrders extends ListRecords
{
    protected static string $resource = DomainOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): DomainOrder {
                    try {
                        return app(DomainOrderService::class)->create(
                            customer: [
                                'name' => $data['customer_name'],
                                'email' => $data['customer_email'],
                                'phone' => $data['customer_phone'] ?? null,
                                'user_id' => $data['user_id'] ?? null,
                            ],
                            domainName: $data['domain_name'],
                            type: DomainOrderType::from(is_string($data['type']) ? $data['type'] : $data['type']->value),
                            years: (int) $data['years'],
                            amount: filled($data['amount'] ?? null) ? (float) $data['amount'] : null,
                        );
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        throw new Halt;
                    }
                }),
        ];
    }
}
