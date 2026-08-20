<?php

namespace App\Filament\Client\Resources;

use App\Enums\DomainOrderType;
use App\Filament\Client\Resources\DomainResource\Pages\ListDomains;
use App\Filament\Client\Resources\DomainResource\Pages\ManageDns;
use App\Filament\Resources\DomainResource as AdminDomainResource;
use App\Models\Domain;
use App\Models\Tld;
use App\Services\DomainOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainResource extends Resource
{
    protected static ?string $model = Domain::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'My Domains';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('name')->copyable(),
                TextEntry::make('lifecycle_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => AdminDomainResource::statusColor($state)),
                TextEntry::make('registered_at')->date(),
                TextEntry::make('expires_at')->date(),
                TextEntry::make('auto_renew')->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'On' : 'Off')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                TextEntry::make('privacy_level')->label('WHOIS privacy')->placeholder('—'),
            ]),
            TextEntry::make('nameservers')
                ->listWithLineBreaks()
                ->placeholder('—')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->copyable(),
                TextColumn::make('lifecycle_status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (?string $state) => AdminDomainResource::statusColor($state)),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->color(fn (Domain $record) => $record->expires_at?->isBefore(now()->addDays(30)) ? 'danger' : null),
                IconColumn::make('auto_renew')->boolean(),
            ])
            ->defaultSort('expires_at')
            ->recordActions([
                ViewAction::make(),
                Action::make('dns')
                    ->label('DNS')
                    ->icon(Heroicon::OutlinedServerStack)
                    ->url(fn (Domain $record) => ManageDns::getUrl(['record' => $record])),
                static::renewAction(),
            ])
            ->emptyStateHeading('No domains yet')
            ->emptyStateDescription('Domains you order from us will appear here once they are active.');
    }

    public static function renewAction(): Action
    {
        return Action::make('renew')
            ->label('Renew')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('success')
            ->schema(function (Domain $record) {
                $tld = Tld::matching($record->name);

                return [
                    Select::make('years')
                        ->options(array_combine(range(1, 5), range(1, 5)))
                        ->default(1)
                        ->required()
                        ->helperText($tld
                            ? '৳'.number_format((float) $tld->renew_price, 0).' per year — payment instructions follow after ordering.'
                            : 'Pricing unavailable for this extension — please contact us.'),
                ];
            })
            ->action(function (Domain $record, array $data) {
                if (Tld::matching($record->name) === null) {
                    Notification::make()
                        ->title('Please contact us to renew this domain')
                        ->warning()
                        ->send();

                    return;
                }

                $user = auth()->user();

                $order = app(DomainOrderService::class)->create(
                    customer: ['name' => $user->name, 'email' => $user->email, 'user_id' => $user->id],
                    domainName: $record->name,
                    type: DomainOrderType::Renew,
                    years: (int) $data['years'],
                );

                Notification::make()
                    ->title("Renewal order {$order->reference} placed")
                    ->body('We have emailed you the payment instructions. The renewal is applied as soon as we confirm your payment.')
                    ->success()
                    ->persistent()
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomains::route('/'),
            'dns' => ManageDns::route('/{record}/dns'),
        ];
    }
}
