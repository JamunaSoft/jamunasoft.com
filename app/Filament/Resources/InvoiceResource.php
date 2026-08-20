<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use App\Services\InvoiceService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class InvoiceResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Invoice::class;

    protected static string $permissionKey = 'billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = Invoice::unpaid()->count();

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
                Select::make('user_id')
                    ->label('Client')
                    ->relationship('user', 'name', fn (Builder $query) => $query->whereDoesntHave('roles'))
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('due_at')
                    ->label('Due date')
                    ->default(now()->addDays(7))
                    ->required(),
            ]),
            Repeater::make('items')
                ->relationship()
                ->schema([
                    TextInput::make('description')->required()->columnSpan(2),
                    TextInput::make('quantity')->numeric()->default(1)->required(),
                    TextInput::make('unit_price')->numeric()->prefix('৳')->required(),
                ])
                ->columns(4)
                ->minItems(1)
                ->required(),
            Grid::make(2)->schema([
                TextInput::make('discount')->numeric()->prefix('৳')->default(0),
                Textarea::make('notes')->rows(2),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('reference')->copyable(),
                TextEntry::make('user.name')->label('Client'),
                TextEntry::make('status')->badge(),
                TextEntry::make('due_at')->date(),
                TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->dateTime(),
            ]),
            RepeatableEntry::make('items')
                ->schema([
                    TextEntry::make('description')->columnSpan(2),
                    TextEntry::make('quantity'),
                    TextEntry::make('total')->money('BDT'),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Grid::make(3)->schema([
                TextEntry::make('discount')->money('BDT'),
                TextEntry::make('total')->money('BDT')->weight('bold'),
                TextEntry::make('amount_paid')->money('BDT'),
            ]),
            RepeatableEntry::make('payments')
                ->schema([
                    TextEntry::make('paid_at')->dateTime(),
                    TextEntry::make('amount')->money('BDT'),
                    TextEntry::make('method')->placeholder('—'),
                    TextEntry::make('transaction_id')->placeholder('—'),
                ])
                ->columns(4)
                ->columnSpanFull()
                ->visible(fn (Invoice $record) => $record->payments->isNotEmpty()),
            TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->copyable(),
                TextColumn::make('user.name')->label('Client')->searchable()->sortable(),
                TextColumn::make('total')->money('BDT')->sortable(),
                TextColumn::make('amount_paid')->money('BDT')->label('Paid')->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'Overdue' : $state->getLabel())
                    ->color(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'danger' : $state->getColor()),
                TextColumn::make('due_at')->date()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(InvoiceStatus::class),
                Filter::make('overdue')
                    ->query(fn (Builder $query) => $query->unpaid()->whereDate('due_at', '<', now())),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('recordPayment')
                    ->label('Record payment')
                    ->icon(Heroicon::OutlinedBanknotes)
                    ->color('success')
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid)
                    ->schema(fn (Invoice $record) => [
                        TextInput::make('amount')
                            ->numeric()
                            ->prefix('৳')
                            ->default($record->balance())
                            ->required(),
                        Select::make('method')
                            ->options([
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'rocket' => 'Rocket',
                                'bank' => 'Bank transfer',
                                'cash' => 'Cash',
                                'other' => 'Other',
                            ])
                            ->required(),
                        TextInput::make('transaction_id')->label('Transaction ID / reference'),
                    ])
                    ->action(function (Invoice $record, array $data) {
                        app(InvoiceService::class)->recordPayment(
                            $record,
                            (float) $data['amount'],
                            $data['method'],
                            $data['transaction_id'] ?? null,
                            recordedBy: auth()->id(),
                        );

                        Notification::make()
                            ->title($record->refresh()->status === InvoiceStatus::Paid
                                ? 'Payment recorded — invoice paid in full'
                                : 'Partial payment recorded')
                            ->success()
                            ->send();
                    }),
                EditAction::make()
                    ->visible(fn (Invoice $record) => $record->status->isOpen())
                    ->after(fn (Invoice $record) => app(InvoiceService::class)->recalculateTotals($record)),
                Action::make('send')
                    ->label('Email to client')
                    ->icon(Heroicon::OutlinedEnvelope)
                    ->color('gray')
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid)
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        app(InvoiceService::class)->sendInvoice($record);

                        Notification::make()->title('Invoice emailed to '.$record->user->email)->success()->send();
                    }),
                Action::make('cancel')
                    ->label('Cancel')
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid && (float) $record->amount_paid === 0.0)
                    ->requiresConfirmation()
                    ->action(function (Invoice $record) {
                        $record->update(['status' => InvoiceStatus::Cancelled]);

                        Notification::make()->title('Invoice cancelled')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }
}
