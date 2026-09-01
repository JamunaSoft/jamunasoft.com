<?php

namespace App\Filament\Client\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Client\Resources\InvoiceResource\Pages\ListInvoices;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'My Invoices';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->where('status', '!=', InvoiceStatus::Draft);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', InvoiceStatus::Unpaid)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('reference')->copyable(),
                TextEntry::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'Overdue' : $state->getLabel())
                    ->color(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'danger' : $state->getColor()),
                TextEntry::make('due_at')->date(),
            ]),
            RepeatableEntry::make('items')
                ->schema([
                    TextEntry::make('title')
                        ->state(fn (InvoiceItem $record) => $record->displayTitle())
                        ->columnSpan(2),
                    TextEntry::make('quantity'),
                    TextEntry::make('total')->money('BDT'),
                    TextEntry::make('description')
                        ->state(fn (InvoiceItem $record) => $record->displayDescription())
                        ->color('gray')
                        ->visible(fn (InvoiceItem $record) => filled($record->displayDescription()))
                        ->columnSpanFull(),
                ])
                ->columns(4)
                ->columnSpanFull(),
            Grid::make(3)->schema([
                TextEntry::make('total')->money('BDT')->weight('bold'),
                TextEntry::make('amount_paid')->money('BDT')->label('Paid'),
                TextEntry::make('balance')
                    ->state(fn (Invoice $record) => $record->balance())
                    ->money('BDT')
                    ->label('Balance due'),
            ]),
            TextEntry::make('payment_instructions')
                ->state(fn () => (string) settings('domain_payment_instructions', ''))
                ->visible(fn (Invoice $record) => $record->status === InvoiceStatus::Unpaid
                    && filled(settings('domain_payment_instructions')))
                ->helperText(fn (Invoice $record) => 'Please mention the invoice number '.$record->reference.' with the payment.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('total')->money('BDT'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'Overdue' : $state->getLabel())
                    ->color(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'danger' : $state->getColor()),
                TextColumn::make('due_at')->date()->sortable(),
                TextColumn::make('created_at')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                Action::make('pdf')
                    ->label('Download PDF')
                    ->icon(Heroicon::OutlinedArrowDownTray)
                    ->color('gray')
                    ->url(fn (Invoice $record) => route('invoices.pdf', ['invoice' => $record, 'download' => 1])),
            ])
            ->emptyStateHeading('No invoices yet');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
        ];
    }
}
