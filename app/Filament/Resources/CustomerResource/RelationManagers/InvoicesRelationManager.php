<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Support\InvoiceActions;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    /**
     * Relation managers on View pages are read-only by default; this one
     * carries the full invoice workflow.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    /** Same invoice form as the main resource, minus the client select. */
    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('billing_profile_id')
                ->label('Billing profile')
                ->options(fn () => $this->getOwnerRecord()->billingProfiles()->pluck('company_name', 'id'))
                ->placeholder('Default (client\'s own details)')
                ->visible(fn () => $this->getOwnerRecord()->billingProfiles()->exists()),
            DatePicker::make('due_at')
                ->label('Due date')
                ->default(now()->addDays(7))
                ->required(),
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
                        ->placeholder('Optional — details shown under the title on the invoice')
                        ->columnSpanFull(),
                ])
                ->columns(6)
                ->minItems(1)
                ->defaultItems(0)
                ->addActionLabel('Add item')
                ->required()
                ->columnSpanFull(),
            Grid::make(2)->schema([
                TextInput::make('discount')->numeric()->prefix('৳')->default(0),
                Textarea::make('notes')->rows(2),
            ])->columnSpanFull(),
            Toggle::make('auto_remind')
                ->label('Automatic payment reminders')
                ->helperText('When on, unpaid/overdue reminders go out every 3 days automatically.'),
        ]);
    }

    public function infolist(Schema $schema): Schema
    {
        return InvoiceResource::infolist($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn ($query) => $query->with('user'))
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('total')->money('BDT'),
                TextColumn::make('amount_paid')->money('BDT')->label('Paid'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'Overdue' : $state->getLabel())
                    ->color(fn (InvoiceStatus $state, Invoice $record) => $record->isOverdue() ? 'danger' : $state->getColor()),
                TextColumn::make('due_at')->date()->sortable(),
                ToggleColumn::make('auto_remind')
                    ->label('Auto remind')
                    ->disabled(fn (Invoice $record) => $record->status !== InvoiceStatus::Unpaid),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('New invoice')
                    ->mutateDataUsing(function (array $data): array {
                        $data['reference'] = Invoice::generateReference();

                        return $data;
                    })
                    ->after(fn (Invoice $record) => app(InvoiceService::class)->recalculateTotals($record))
                    ->successNotificationTitle('Invoice created — not emailed yet, use "Email to client" when ready'),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make([
                    Action::make('edit')
                        ->label('Edit')
                        ->icon(Heroicon::OutlinedPencilSquare)
                        ->visible(fn (Invoice $record) => $record->status->isOpen())
                        ->url(fn (Invoice $record) => InvoiceResource::getUrl('edit', ['record' => $record])),
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
}
