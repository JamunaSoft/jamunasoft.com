<?php

namespace App\Filament\Client\Pages;

use App\Enums\TicketPriority;
use App\Models\HostingPlan;
use App\Models\Package;
use App\Services\TicketService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class OrderServices extends Page
{
    protected string $view = 'filament.client.pages.order-services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $navigationLabel = 'Order Services';

    protected static ?string $title = 'Order Services';

    protected static ?int $navigationSort = 6;

    /** @return Collection<int, HostingPlan> */
    public function getHostingPlansProperty(): Collection
    {
        return HostingPlan::query()
            ->where('is_active', true)
            ->orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->get();
    }

    /** @return Collection<int, Package> */
    public function getPackagesProperty(): Collection
    {
        return Package::query()
            ->where('is_active', true)
            ->orderByDesc('is_recommended')
            ->orderBy('sort_order')
            ->get();
    }

    public function requestServiceAction(): Action
    {
        return Action::make('requestService')
            ->modalHeading(fn (array $arguments): string => 'Request — '.($this->resolveProduct($arguments)?->name ?? 'service'))
            ->modalDescription('Send us an order request. We will confirm availability and email you an invoice — nothing is charged automatically.')
            ->modalSubmitActionLabel('Send request')
            ->schema([
                TextInput::make('domain')
                    ->label('Domain name (if applicable)')
                    ->placeholder('example.com'),
                Textarea::make('notes')
                    ->label('Anything we should know?')
                    ->rows(3),
            ])
            ->action(function (array $data, array $arguments): void {
                $product = $this->resolveProduct($arguments);

                if ($product === null) {
                    Notification::make()->title('This product is no longer available.')->danger()->send();

                    return;
                }

                $kind = $product instanceof HostingPlan ? 'Hosting plan' : 'Package';

                $ticket = app(TicketService::class)->open(
                    user: auth()->user(),
                    subject: "Order request: {$product->name}",
                    message: implode("\n", [
                        'Service order request from the client panel.',
                        '',
                        "Product: {$product->name} ({$kind})",
                        'Domain: '.(filled($data['domain'] ?? null) ? $data['domain'] : '—'),
                        'Notes: '.(filled($data['notes'] ?? null) ? $data['notes'] : '—'),
                    ]),
                    priority: TicketPriority::Normal,
                );

                Notification::make()
                    ->title('Request sent — we will confirm with an invoice shortly')
                    ->body("Track it in Support Tickets ({$ticket->reference}).")
                    ->success()
                    ->send();
            });
    }

    protected function resolveProduct(array $arguments): HostingPlan|Package|Model|null
    {
        return match ($arguments['type'] ?? null) {
            'hosting' => HostingPlan::query()->where('is_active', true)->find($arguments['id'] ?? null),
            'package' => Package::query()->where('is_active', true)->find($arguments['id'] ?? null),
            default => null,
        };
    }
}
