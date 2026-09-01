<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use App\Services\InvoiceService;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    public ?float $pendingOpeningBalance = null;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // No password field on create: the account gets a random
                // password and the client sets their own via the emailed link.
                ->mutateDataUsing(function (array $data): array {
                    $this->pendingOpeningBalance = (float) ($data['opening_balance'] ?? 0);
                    unset($data['opening_balance']);

                    $data['password'] = str()->random(40);

                    return $data;
                })
                ->after(function (User $record) {
                    if ($this->pendingOpeningBalance > 0) {
                        app(InvoiceService::class)->create(
                            userId: $record->id,
                            items: [[
                                'title' => 'Previous balance',
                                'description' => 'Dues carried over from before '.now()->format('M Y'),
                                'unit_price' => $this->pendingOpeningBalance,
                            ]],
                            sendEmail: false,
                        );
                    }

                    CustomerResource::sendSetPasswordLink($record);
                })
                ->successNotificationTitle('Client created — a set-password link was emailed to them'),
        ];
    }
}
