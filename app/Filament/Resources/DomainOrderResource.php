<?php

namespace App\Filament\Resources;

use App\Enums\DomainOrderStatus;
use App\Enums\DomainOrderType;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\DomainOrderResource\Pages\ListDomainOrders;
use App\Jobs\ProcessDomainOrder;
use App\Models\DomainOrder;
use App\Models\User;
use App\Services\DomainOrderService;
use App\Services\Spaceship\SpaceshipClient;
use App\Services\Spaceship\SpaceshipException;
use App\Support\DomainName;
use App\Support\RegistrantContact;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
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
                    ->live()
                    ->default(DomainOrderType::Register)
                    ->required(),
                TextInput::make('domain_name')
                    ->required()
                    ->placeholder('example.com')
                    ->dehydrateStateUsing(fn (string $state) => DomainName::normalize($state)),
                TextInput::make('customer_name')
                    ->visible(fn (Get $get) => ! in_array($get('type'), [DomainOrderType::Register, DomainOrderType::Register->value], true))
                    ->required(),
                TextInput::make('customer_email')->email()
                    ->visible(fn (Get $get) => ! in_array($get('type'), [DomainOrderType::Register, DomainOrderType::Register->value], true))
                    ->required(),
                TextInput::make('customer_phone')
                    ->visible(fn (Get $get) => ! in_array($get('type'), [DomainOrderType::Register, DomainOrderType::Register->value], true)),
                Select::make('user_id')
                    ->label('Panel customer')
                    ->relationship('user', 'name', fn (Builder $query) => $query->whereDoesntHave('roles'))
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->selectLabel())
                    ->searchable(['name', 'email', 'company_name'])
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (?int $state, Set $set): void {
                        if ($state === null) {
                            return;
                        }

                        $customer = User::query()->find($state);

                        if ($customer === null) {
                            return;
                        }

                        $set('customer_name', $customer->name);
                        $set('customer_email', $customer->email);
                        $set('customer_phone', $customer->phone);
                        foreach (RegistrantContact::fromCustomer($customer->toArray()) as $field => $value) {
                            $set('meta.registrant.'.$field, $value);
                        }
                    })
                    ->placeholder('None yet'),
                Select::make('years')
                    ->options(array_combine(range(1, 5), range(1, 5)))
                    ->default(1)
                    ->required(),
                TextInput::make('amount')
                    ->numeric()
                    ->prefix('৳')
                    ->helperText('Leave empty to price automatically from the TLD table.'),
                ...static::registrantFields(),
                TextInput::make('meta.epp_code')
                    ->label('EPP / Auth code')
                    ->password()
                    ->revealable()
                    ->visible(fn (Get $get): bool => in_array($get('type'), [DomainOrderType::Transfer, DomainOrderType::Transfer->value], true))
                    ->dehydrated(fn (Get $get): bool => in_array($get('type'), [DomainOrderType::Transfer, DomainOrderType::Transfer->value], true))
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function registrantFields(): array
    {
        $fields = [
            'firstName' => 'Customer first name',
            'lastName' => 'Customer last name',
            'email' => 'Registrant email',
            'phone' => 'Registrant phone',
            'organization' => 'Organization (optional)',
            'address1' => 'Street address',
            'address2' => 'Address line 2 (optional)',
            'city' => 'City',
            'stateProvince' => 'State / province',
            'postalCode' => 'Postal code',
            'country' => 'Country code',
        ];

        return array_map(function (string $field, string $label) {
            $input = TextInput::make('meta.registrant.'.$field)
                ->label($label)
                ->visible(fn (Get $get) => in_array($get('type'), [null, DomainOrderType::Register, DomainOrderType::Register->value], true))
                ->required(fn (Get $get) => in_array($get('type'), [null, DomainOrderType::Register, DomainOrderType::Register->value], true)
                    && ! in_array($field, ['organization', 'address2', 'stateProvince']))
                ->maxLength(match ($field) {
                    'country' => 2,
                    'phone' => 17,
                    'postalCode' => 16,
                    'firstName', 'lastName' => 125,
                    'address1', 'address2', 'city', 'stateProvince' => 64,
                    default => 255,
                });
            if ($field === 'stateProvince') {
                $input->helperText('Fill in when required for the customer’s country.');
            }
            if ($field === 'email') {
                $input->email();
            } elseif ($field === 'phone') {
                $input->regex('/^\+\d{1,3}\.\d{4,12}$/')->helperText('Include country code, e.g. +880.1700000000');
            } elseif ($field === 'country') {
                $input->regex('/^[A-Za-z]{2}$/')->placeholder('BD')
                    ->dehydrateStateUsing(fn ($state) => strtoupper(trim($state)));
            }

            return $input;
        }, array_keys($fields), array_values($fields));
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
                TextEntry::make('meta.source_registrar')->label('Transfer source')->placeholder('—'),
                TextEntry::make('meta.transfer_status')->label('Registrar transfer status')->placeholder('Not checked yet'),
                TextEntry::make('meta.transfer_checked_at')->label('Transfer last checked')->dateTime()->placeholder('—'),
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
                TextColumn::make('reference')->searchable()->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('domain_name')->searchable()->sortable()->description(fn (DomainOrder $record) => $record->status === DomainOrderStatus::Failed ? str($record->error_message)->limit(110)->toString() : null),
                TextColumn::make('customer_name')->searchable()->toggleable(),
                TextColumn::make('registrar')->badge()->color('gray')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('years')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount')->money('BDT')->sortable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime('M j, Y')->sortable()->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->persistFiltersInSession()
            ->filters([
                SelectFilter::make('status')->options(DomainOrderStatus::class),
                SelectFilter::make('type')->options(DomainOrderType::class),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('editContact')
                    ->label('Edit customer contact')
                    ->authorize(fn (DomainOrder $record) => static::canEdit($record)
                        && $record->type === DomainOrderType::Register
                        && in_array($record->status, [DomainOrderStatus::PendingPayment, DomainOrderStatus::Paid, DomainOrderStatus::Failed], true))
                    ->visible(fn (DomainOrder $record) => $record->type === DomainOrderType::Register
                        && in_array($record->status, [DomainOrderStatus::PendingPayment, DomainOrderStatus::Paid, DomainOrderStatus::Failed], true))
                    ->fillForm(fn (DomainOrder $record) => ['meta' => ['registrant' => RegistrantContact::forOrder($record)]])
                    ->schema(static::registrantFields())
                    ->action(function (DomainOrder $record, array $data) {
                        $contact = RegistrantContact::validate(data_get($data, 'meta.registrant', []));
                        $record->update([
                            'customer_name' => $contact['firstName'].' '.$contact['lastName'],
                            'customer_email' => $contact['email'],
                            'customer_phone' => $contact['phone'],
                            'meta' => array_merge($record->meta ?? [], ['registrant' => $contact]),
                        ]);
                        Notification::make()->title('Customer contact saved — you can retry the order')->success()->send();
                    }),
                Action::make('checkTransfer')
                    ->label('Check transfer')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->authorize(fn (DomainOrder $record) => static::canEdit($record))
                    ->visible(fn (DomainOrder $record) => $record->type === DomainOrderType::Transfer && $record->status === DomainOrderStatus::Processing && $record->registrar === 'spaceship')
                    ->action(function (DomainOrder $record) {
                        if (data_get($record->meta, 'transfer_checked_at') && Carbon::parse(data_get($record->meta, 'transfer_checked_at'))->gt(now()->subMinute())) {
                            Notification::make()->title('Recently checked: '.data_get($record->meta, 'transfer_status', 'processing'))->info()->send();

                            return;
                        }
                        try {
                            $transfer = app(SpaceshipClient::class)->getTransfer($record->domain_name);
                            $status = (string) data_get($transfer, 'status', 'unknown');
                            $record->update(['meta' => array_merge($record->meta ?? [], ['transfer_status' => $status, 'transfer_checked_at' => now()->toIso8601String()])]);
                            Notification::make()->title('Registrar transfer status: '.$status)->body('Monitoring will update the order automatically. This check does not submit a new transfer.')->info()->send();
                        } catch (SpaceshipException $e) {
                            Notification::make()->title('Could not check transfer')->body($e->getMessage())->warning()->send();
                        }
                    }),
                Action::make('confirmPayment')
                    ->authorize(fn (DomainOrder $record) => static::canEdit($record))
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
                    ->authorize(fn (DomainOrder $record) => static::canEdit($record))
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
                    ->authorize(fn (DomainOrder $record) => static::canEdit($record))
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
