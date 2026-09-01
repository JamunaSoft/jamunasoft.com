<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                // No password field on create: the account gets a random
                // password and the client sets their own via the emailed link.
                ->mutateDataUsing(function (array $data): array {
                    $data['password'] = str()->random(40);

                    return $data;
                })
                ->after(fn (User $record) => CustomerResource::sendSetPasswordLink($record))
                ->successNotificationTitle('Client created — a set-password link was emailed to them'),
        ];
    }
}
