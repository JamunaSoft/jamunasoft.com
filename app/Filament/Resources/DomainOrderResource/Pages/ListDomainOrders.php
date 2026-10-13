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

    protected static function customerContact(array $data): array
    {
        $contact = data_get($data, 'meta.registrant', []);
        $mapping = ['first_name' => 'firstName', 'last_name' => 'lastName', 'company_name' => 'organization',
            'email' => 'email', 'phone' => 'phone', 'address' => 'address1', 'address2' => 'address2',
            'city' => 'city', 'state' => 'stateProvince', 'postal_code' => 'postalCode', 'country' => 'country'];
        $customer = [];
        foreach ($mapping as $field => $key) {
            if (filled($contact[$key] ?? null)) {
                $customer[$field] = $contact[$key];
            }
        }
        if (isset($customer['first_name'], $customer['last_name'])) {
            $customer['name'] = $customer['first_name'].' '.$customer['last_name'];
        }

        return $customer;
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->using(function (array $data): DomainOrder {
                    try {
                        $order = app(DomainOrderService::class)->create(
                            customer: [
                                'name' => $data['customer_name'] ?? '',
                                'email' => $data['customer_email'] ?? '',
                                'phone' => $data['customer_phone'] ?? null,
                                'user_id' => $data['user_id'] ?? null,
                                ...static::customerContact($data),
                            ],
                            domainName: $data['domain_name'],
                            type: DomainOrderType::from(is_string($data['type']) ? $data['type'] : $data['type']->value),
                            years: (int) $data['years'],
                            amount: filled($data['amount'] ?? null) ? (float) $data['amount'] : null,
                        );
                        if ($order->type === DomainOrderType::Transfer && filled(data_get($data, 'meta.epp_code'))) {
                            $order->update(['meta' => array_merge($order->meta ?? [], ['epp_code' => data_get($data, 'meta.epp_code')])]);
                        }

                        return $order;
                    } catch (\InvalidArgumentException $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();

                        throw new Halt;
                    }
                }),
        ];
    }
}
