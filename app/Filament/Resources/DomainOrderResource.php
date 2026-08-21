<?php

namespace App\Filament\Resources;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\DomainOrderResource\Pages\ListDomainOrders;
use App\Jobs\ProcessDomainOrder;
use App\Models\DomainOrder;
use App\Services\DomainOrderService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class DomainOrderResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = DomainOrder::class;

    protected static string $permissionKey = 'domains';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static string|UnitEnum|null $navigationGroup = 'Domains';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = DomainOrder::whereIn('status', [DomainOrderStatus::PendingPayment, DomainOrderStatus::Failed])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('type')
                    ->options(DomainOrderType::class)
                    ->default(DomainOrderType::Register)
                    ->required(),
                TextInput::make('domain_name')
                    ->required()
                    ->placeholder('example.com')
                    ->dehydrateStateUsing(fn (string $state) => strtolower(trim($state))),
                TextInput::make('customer_name')->required(),
                TextInput::make('customer_email')->email()->required(),
                TextInput::make('customer_phone'),
                Select::make('user_id')
                    ->label('Panel customer')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('None yet'),
                Select::make('years')
                    ->options(array_combine(range(1, 5), range(1, 5)))
                    ->default(1)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('৳')
                    ->helperText('Leave empty to price automatically from the TLD table.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('reference')->copyable(),
                TextEntry::make('status')->badge(),
                TextEntry::make('domain_name')->copyable(),
                TextEntry::make('type')->badge()->color('gray'),
                TextEntry::make('customer_name'),
                TextEntry::make('customer_email')->copyable(),
                TextEntry::make('customer_phone')->placeholder('—'),
                TextEntry::make('user.name')->label('Panel customer')->placeholder('—'),
                TextEntry::make('years'),
                TextEntry::make('amount')->money('BDT'),
                TextEntry::make('payment_method')->placeholder('—'),
                TextEntry::make('payment_reference')->placeholder('—'),
                TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                TextEntry::make('completed_at')->dateTime()->placeholder('—'),
                TextEntry::make('spaceship_operation_id')->placeholder('—')->copyable(),
                TextEntry::make('created_at')->dateTime(),
            ]),
            TextEntry::make('error_message')
                ->color('danger')
                ->visible(fn (DomainOrder $record) => filled($record->error_message))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->copyable(),
                TextColumn::make('domain_name')->searchable()->sortable(),
                TextColumn::make('customer_name')->searchable()->toggleable(),
                TextColumn::make('registrar')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('years')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('BDT')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(DomainOrderStatus::class),
                SelectFilter::make('type')->options(DomainOrderType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('confirmPayment')
                    ->label('Confirm payment')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->visible(fn (DomainOrder $record) => $record->status === DomainOrderStatus::PendingPayment)
                    ->schema([
                        Select::make('payment_method')
                            ->options([
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'rocket' => 'Rocket',
                                'bank' => 'Bank transfer',
                                'cash' => 'Cash',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('payment_reference')
                            ->label('Transaction ID / reference'),
                    ])
                    ->action(function (DomainOrder $record, array $data) {
                        app(DomainOrderService::class)->markPaid($record, $data['payment_method'], $data['payment_reference'] ?? null);

                        Notification::make()
                            ->title('Payment confirmed — registrar processing started')
                            ->success()
                            ->send();
                    }),
                Action::make('retry')
                    ->label('Retry')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->visible(fn (DomainOrder $record) => $record->status === DomainOrderStatus::Failed)
                    ->requiresConfirmation()
                    ->modalDescription('Re-run the registrar operation for this order. Make sure the previous failure was resolved (e.g. balance topped up) and that the operation did not already succeed at Spaceship.')
                    ->action(function (DomainOrder $record) {
                        ProcessDomainOrder::dispatch($record);

                        Notification::make()
                            ->title('Order queued for processing')
                            ->success()
                            ->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (DomainOrder $record) => $record->status === DomainOrderStatus::PendingPayment)
                    ->requiresConfirmation()
                    ->action(function (DomainOrder $record) {
                        $record->update(['status' => DomainOrderStatus::Cancelled]);

                        Notification::make()
                            ->title('Order cancelled')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainOrders::route('/'),
        ];
    }
}
