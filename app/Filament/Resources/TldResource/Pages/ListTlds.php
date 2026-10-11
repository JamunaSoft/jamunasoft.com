<?php

namespace App\Filament\Resources\TldResource\Pages;

use App\Filament\Resources\TldResource;
use App\Services\TldPriceSyncService;
use App\Services\WhoisRetailPricing;
use App\Support\Settings;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\HtmlString;
use Throwable;

class ListTlds extends ListRecords
{
    protected static string $resource = TldResource::class;

    public function getSubheading(): HtmlString
    {
        $status = 'USD/BDT: '.settings('usd_bdt_rate', 'Not synced').' · Updated: '.settings('usd_bdt_updated_at', 'Never')
            .(settings('tld_sync_error') ? ' · Last sync error: '.settings('tld_sync_error') : '');

        return new HtmlString(e($status).' · <a href="https://www.exchangerate-api.com" target="_blank" rel="noopener noreferrer">Rates by ExchangeRate-API</a>');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pricingSettings')->label('Pricing settings')
                ->visible(fn () => TldResource::canCreate())
                ->fillForm(fn () => [
                    'tld_auto_price_sync' => filter_var(settings('tld_auto_price_sync', true), FILTER_VALIDATE_BOOL),
                    'tld_margin_percent' => settings('tld_margin_percent', 0),
                    'tld_margin_bdt' => settings('tld_margin_bdt', 0),
                    'tld_exchange_buffer_percent' => settings('tld_exchange_buffer_percent', 0),
                ])
                ->schema([
                    Toggle::make('tld_auto_price_sync')->label('Daily automatic sync (05:00)'),
                    TextInput::make('tld_margin_percent')->label('Profit margin (%)')->numeric()->minValue(0)->maxValue(1000)->required(),
                    TextInput::make('tld_margin_bdt')->label('Additional profit (BDT)')->numeric()->minValue(0)->maxValue(1000000)->required(),
                    TextInput::make('tld_exchange_buffer_percent')->label('Currency/card fee buffer (%)')->numeric()->minValue(0)->maxValue(100)->required()
                        ->helperText('BDT price = USD × rate × (1 + buffer %) × (1 + margin %) + fixed profit, rounded up to the next taka. Applies at the next successful sync.'),
                ])
                ->action(function (array $data) {
                    abort_unless(TldResource::canCreate(), 403);
                    Settings::set($data, 'domains');
                    Notification::make()->title('Pricing settings saved')->success()->send();
                }),
            Action::make('importCatalog')->label('Add all TLDs')
                ->visible(fn () => TldResource::canCreate())
                ->action(function () {
                    abort_unless(TldResource::canCreate(), 403);
                    Artisan::call('domains:import-tld-catalog');
                    Notification::make()->title('TLD catalog added')->body(trim(Artisan::output()))->success()->send();
                }),
            Action::make('syncPrices')->label('Sync prices now')
                ->visible(fn () => TldResource::canCreate())
                ->action(function (TldPriceSyncService $sync, WhoisRetailPricing $source) {
                    abort_unless(TldResource::canCreate(), 403);
                    try {
                        $rate = $sync->updateExchangeRate();
                        $sync->reprice($rate);
                        $count = $sync->apply($source->fetch(), $rate);
                        Settings::set(['tld_sync_error' => null], 'domains');
                        Notification::make()->title("Updated {$count} TLD prices")->success()->send();
                    } catch (Throwable $e) {
                        Settings::set(['tld_sync_error' => $e->getMessage()], 'domains');
                        Notification::make()->title('Price sync failed')->body($e->getMessage())->danger()->send();
                    }
                }),
            CreateAction::make(),
        ];
    }
}
