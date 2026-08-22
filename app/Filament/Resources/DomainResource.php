<?php

namespace App\Filament\Resources;

use App\Enums\DomainOrderType;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\DomainResource\Pages\ListDomains;
use App\Models\Domain;
use App\Models\Tld;
use App\Services\DomainOrderService;
use App\Services\Registrars\RegistrarException;
use App\Services\Registrars\RegistrarManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class DomainResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Domain::class;

    protected static string $permissionKey = 'domains';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static string|UnitEnum|null $navigationGroup = 'Domains';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        $count = Domain::expiringWithin(30)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('user_id')
                ->label('Customer')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->placeholder('Unassigned (company-owned)')
                ->helperText('Panel customer who owns this domain. Registrar-side data is managed via Spaceship sync.'),
            Textarea::make('internal_notes')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('name')->copyable(),
                TextEntry::make('user.name')->label('Customer')->placeholder('Unassigned (company-owned)'),
                TextEntry::make('lifecycle_status')->badge()->color(fn (?string $state) => static::statusColor($state)),
                TextEntry::make('verification_status')->placeholder('—'),
                TextEntry::make('registered_at')->date(),
                TextEntry::make('expires_at')->date(),
                TextEntry::make('auto_renew')->badge()
                    ->formatStateUsing(fn (bool $state) => $state ? 'On' : 'Off')
                    ->color(fn (bool $state) => $state ? 'success' : 'gray'),
                TextEntry::make('privacy_level')->placeholder('—'),
                TextEntry::make('nameserver_provider')->placeholder('—'),
                TextEntry::make('last_synced_at')->since(),
            ]),
            TextEntry::make('nameservers')
                ->listWithLineBreaks()
                ->placeholder('—')
                ->columnSpanFull(),
            TextEntry::make('internal_notes')->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable()->copyable(),
                TextColumn::make('registrar')->badge()->color('gray')->toggleable(),
                TextColumn::make('user.name')
                    ->label('Customer')
                    ->placeholder('Unassigned')
                    ->toggleable(),
                TextColumn::make('lifecycle_status')
                    ->badge()
                    ->color(fn (?string $state) => static::statusColor($state)),
                TextColumn::make('expires_at')
                    ->date()
                    ->sortable()
                    ->color(fn (Domain $record) => $record->expires_at?->isBefore(now()->addDays(30)) ? 'danger' : null),
                IconColumn::make('auto_renew')->boolean()->toggleable(),
                TextColumn::make('last_synced_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('expires_at')
            ->filters([
                SelectFilter::make('lifecycle_status')
                    ->options(fn () => Domain::query()
                        ->whereNotNull('lifecycle_status')
                        ->distinct()
                        ->pluck('lifecycle_status', 'lifecycle_status')
                        ->all()),
                TernaryFilter::make('auto_renew'),
                Filter::make('expiring_soon')
                    ->label('Expiring within 30 days')
                    ->query(fn (Builder $query) => $query->whereNotNull('expires_at')
                        ->whereBetween('expires_at', [now(), now()->addDays(30)])),
                Filter::make('unassigned')
                    ->label('Unassigned only')
                    ->query(fn (Builder $query) => $query->whereNull('user_id')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('renew')
                    ->label('Renew')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('success')
                    ->visible(fn (Domain $record) => $record->user_id !== null && Tld::matching($record->name) !== null)
                    ->schema(fn (Domain $record) => [
                        Select::make('years')
                            ->options(array_combine(range(1, 5), range(1, 5)))
                            ->default(1)
                            ->required()
                            ->helperText('৳'.number_format((float) Tld::matching($record->name)?->renew_price, 0).' per year — creates a renewal order + invoice for '.$record->user?->email.'.'),
                    ])
                    ->action(function (Domain $record, array $data) {
                        $order = app(DomainOrderService::class)->create(
                            customer: ['name' => $record->user->name, 'email' => $record->user->email, 'user_id' => $record->user_id],
                            domainName: $record->name,
                            type: DomainOrderType::Renew,
                            years: (int) $data['years'],
                        );

                        Notification::make()
                            ->title("Renewal order {$order->reference} created and invoiced")
                            ->body('Confirm the payment on the Domain Orders page once received.')
                            ->success()
                            ->send();
                    }),
                Action::make('syncOne')
                    ->label('Sync')
                    ->icon(Heroicon::OutlinedCloudArrowDown)
                    ->color('gray')
                    ->action(function (Domain $record) {
                        try {
                            app(RegistrarManager::class)->for($record->registrar)->syncDomain($record->name);
                            Notification::make()->title('Domain synced from '.$record->registrar)->success()->send();
                        } catch (RegistrarException $e) {
                            Notification::make()->title('Sync failed')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Action::make('eppCode')
                    ->label('EPP code')
                    ->icon(Heroicon::OutlinedKey)
                    ->color('gray')
                    ->visible(fn (Domain $record) => filled(data_get($record->meta, 'domsecret')))
                    ->modalHeading(fn (Domain $record) => 'EPP / transfer code — '.$record->name)
                    ->modalDescription(fn (Domain $record) => (string) data_get($record->meta, 'domsecret'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function statusColor(?string $state): string
    {
        return match ($state) {
            'registered', 'active' => 'success',
            'expiring', 'renewalGracePeriod', 'gracePeriod' => 'warning',
            'expired', 'redemptionPeriod', 'pendingDelete' => 'danger',
            default => 'gray',
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomains::route('/'),
        ];
    }
}
