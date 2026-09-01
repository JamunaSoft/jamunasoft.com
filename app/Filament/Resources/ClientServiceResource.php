<?php

namespace App\Filament\Resources;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\ClientServiceResource\Pages\ListClientServices;
use App\Models\ClientService;
use App\Models\User;
use App\Services\InvoiceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ClientServiceResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = ClientService::class;

    protected static string $permissionKey = 'billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Services';

    protected static ?string $modelLabel = 'Service';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                Select::make('user_id')
                    ->label('Client')
                    ->relationship('user', 'name', fn (Builder $query) => $query->whereDoesntHave('roles'))
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->selectLabel())
                    ->searchable(['name', 'email', 'company_name'])
                    ->preload()
                    ->required(),
                Select::make('hosting_plan_id')
                    ->label('Hosting plan (optional)')
                    ->relationship('hostingPlan', 'name')
                    ->placeholder('Custom service'),
                TextInput::make('name')
                    ->required()
                    ->placeholder('Web Hosting — Basic')
                    ->helperText('What appears on invoices.'),
                TextInput::make('domain')->placeholder('example.com'),
                Select::make('billing_cycle')
                    ->options(BillingCycle::class)
                    ->default(BillingCycle::Yearly)
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->helperText('Per billing cycle.'),
                DatePicker::make('next_due_at')
                    ->label('Next due date')
                    ->required(),
                Select::make('status')
                    ->options(ClientServiceStatus::class)
                    ->default(ClientServiceStatus::Active)
                    ->required(),
            ]),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('user.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('domain')->placeholder('—')->toggleable(),
                TextColumn::make('billing_cycle')->badge()->color('gray'),
                TextColumn::make('price')->money('BDT')->sortable(),
                TextColumn::make('next_due_at')
                    ->label('Next due')
                    ->date()
                    ->sortable()
                    ->color(fn (ClientService $record) => $record->next_due_at?->isBefore(now()->addDays(7)) ? 'danger' : null),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('next_due_at')
            ->filters([
                SelectFilter::make('status')->options(ClientServiceStatus::class),
                SelectFilter::make('billing_cycle')->options(BillingCycle::class),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('invoiceNow')
                    ->label('Invoice now')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('success')
                    ->visible(fn (ClientService $record) => $record->status === ClientServiceStatus::Active && ! $record->hasOpenInvoice())
                    ->requiresConfirmation()
                    ->modalDescription('Create an invoice for the next billing period of this service. It is not emailed automatically — review it, then use "Email to client".')
                    ->action(function (ClientService $record) {
                        $invoice = app(InvoiceService::class)->create(
                            userId: $record->user_id,
                            items: [[
                                'title' => sprintf(
                                    '%s — %s (due %s)',
                                    $record->name,
                                    $record->billing_cycle->getLabel(),
                                    $record->next_due_at?->format('d M Y') ?? 'now',
                                ),
                                'unit_price' => (float) $record->price,
                                'item_type' => 'client_service',
                                'item_id' => $record->id,
                            ]],
                            dueAt: $record->next_due_at,
                            sendEmail: false,
                        );

                        Notification::make()
                            ->title("Invoice {$invoice->reference} created — review it, then email it to the client")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientServices::route('/'),
        ];
    }
}
