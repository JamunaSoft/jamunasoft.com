<?php

namespace App\Filament\Resources;

use App\Enums\QuotationStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\QuotationResource\Pages\ListQuotations;
use App\Models\Quotation;
use App\Services\QuotationService;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class QuotationResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Quotation::class;

    protected static string $permissionKey = 'quotations';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCurrencyDollar;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Leads';

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        $count = Quotation::where('status', QuotationStatus::Accepted)->whereNull('invoice_id')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('customer_name')->required(),
                TextInput::make('customer_email')->email()->required(),
                Select::make('lead_id')
                    ->label('Linked lead (optional)')
                    ->relationship('lead', 'reference')
                    ->searchable()
                    ->preload(),
                DatePicker::make('valid_until')
                    ->default(now()->addDays(14)),
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
                Textarea::make('notes')
                    ->rows(2)
                    ->helperText('Shown to the customer on the quotation page.'),
            ]),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('reference')->copyable(),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (QuotationStatus $state, Quotation $record) => $record->isExpired() ? 'Expired' : $state->getLabel())
                    ->color(fn (QuotationStatus $state, Quotation $record) => $record->isExpired() ? 'gray' : $state->getColor()),
                TextEntry::make('valid_until')->date()->placeholder('—'),
                TextEntry::make('customer_name'),
                TextEntry::make('customer_email')->copyable(),
                TextEntry::make('lead.reference')->label('Lead')->placeholder('—'),
                TextEntry::make('sent_at')->dateTime()->placeholder('—'),
                TextEntry::make('responded_at')->dateTime()->placeholder('—'),
                TextEntry::make('invoice.reference')->label('Invoice')->placeholder('—'),
            ]),
            RepeatableEntry::make('items')
                ->schema([
                    TextEntry::make('description')->columnSpan(2),
                    TextEntry::make('quantity'),
                    TextEntry::make('total')->money('BDT'),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Grid::make(2)->schema([
                TextEntry::make('discount')->money('BDT'),
                TextEntry::make('total')->money('BDT')->weight('bold'),
            ]),
            TextEntry::make('publicUrl')
                ->label('Public link')
                ->state(fn (Quotation $record) => $record->publicUrl())
                ->copyable()
                ->visible(fn (Quotation $record) => $record->status !== QuotationStatus::Draft)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->copyable(),
                TextColumn::make('customer_name')->searchable()->sortable(),
                TextColumn::make('total')->money('BDT')->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (QuotationStatus $state, Quotation $record) => $record->isExpired() ? 'Expired' : $state->getLabel())
                    ->color(fn (QuotationStatus $state, Quotation $record) => $record->isExpired() ? 'gray' : $state->getColor()),
                TextColumn::make('valid_until')->date()->sortable()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(QuotationStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Quotation $record) => in_array($record->status, [QuotationStatus::Draft, QuotationStatus::Sent], true))
                    ->after(fn (Quotation $record) => app(QuotationService::class)->recalculateTotals($record)),
                Action::make('send')
                    ->label(fn (Quotation $record) => $record->status === QuotationStatus::Draft ? 'Send' : 'Resend')
                    ->icon(Heroicon::OutlinedPaperAirplane)
                    ->color('success')
                    ->visible(fn (Quotation $record) => in_array($record->status, [QuotationStatus::Draft, QuotationStatus::Sent], true))
                    ->requiresConfirmation()
                    ->modalDescription(fn (Quotation $record) => 'Email the quotation to '.$record->customer_email.' with its acceptance link.')
                    ->action(function (Quotation $record) {
                        app(QuotationService::class)->send($record);

                        Notification::make()->title('Quotation sent to '.$record->customer_email)->success()->send();
                    }),
                Action::make('convert')
                    ->label('Convert to invoice')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('primary')
                    ->visible(fn (Quotation $record) => $record->status === QuotationStatus::Accepted && $record->invoice_id === null)
                    ->requiresConfirmation()
                    ->modalDescription('Create and email an invoice from this quotation. A client account is created automatically if needed.')
                    ->action(function (Quotation $record) {
                        $invoice = app(QuotationService::class)->convertToInvoice($record);

                        Notification::make()
                            ->title("Invoice {$invoice->reference} created and emailed")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotations::route('/'),
        ];
    }
}
