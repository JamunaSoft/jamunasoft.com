<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\InvoiceResource\Pages\EditInvoice;
use App\Filament\Resources\InvoiceResource\Pages\ListInvoices;
use App\Filament\Support\InvoiceActions;
use App\Models\BillingProfile;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
                    ->getOptionLabelFromRecordUsing(fn (User $record) => $record->selectLabel())
                    ->searchable(['name', 'email', 'company_name'])
                    ->preload()
                    ->live()
                    ->required(),
                Select::make('billing_profile_id')
                    ->label('Billing profile')
                    ->options(fn (Get $get) => BillingProfile::where('user_id', $get('user_id'))->pluck('company_name', 'id'))
                    ->placeholder('Default (client\'s own details)')
                    ->visible(fn (Get $get) => filled($get('user_id'))
                        && BillingProfile::where('user_id', $get('user_id'))->exists())
                    ->helperText('Which company this invoice is billed to.'),
                DatePicker::make('due_at')
                    ->label('Due date')
                    ->default(now()->addDays(7))
                    ->required(),
            ]),
            Repeater::make('items')
                ->relationship()
                ->orderColumn('sort_order')
                ->reorderableWithButtons()
                ->schema([
                    TextInput::make('title')->required()->columnSpan(3),
                    TextInput::make('quantity')->numeric()->default(1)->required(),
                    TextInput::make('unit_price')->numeric()->prefix('৳')->required()->columnSpan(2),
                    Textarea::make('description')
                        ->rows(2)
                        ->placeholder('Optional — details shown under the title on the invoice, e.g. specs, domain, duration')
                        ->columnSpanFull(),
                ])
                ->columns(6)
                ->minItems(1)
                ->required()
                ->columnSpanFull(),
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
                TextEntry::make('billingProfile.company_name')
                    ->label('Billed to')
                    ->placeholder('Default profile'),
                TextEntry::make('status')->badge(),
                TextEntry::make('due_at')->date(),
                TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->dateTime(),
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
                ActionGroup::make([
                    EditAction::make(),
                    ...InvoiceActions::recordActions(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    InvoiceActions::mergeBulkAction(),
                    InvoiceActions::emailBundleBulkAction(),
                ]),
            ]);
    }

    /**
     * WHMCS-style: invoices are edited on a full page, and only while open.
     */
    public static function canEdit(Model $record): bool
    {
        /** @var Invoice $record */
        return static::userCan('manage') && $record->status->isOpen();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListInvoices::route('/'),
            'create' => CreateInvoice::route('/create'),
            'edit' => EditInvoice::route('/{record}/edit'),
        ];
    }
}
