<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Models\Quotation;
use App\Models\User;
use App\Services\QuotationService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuotationsRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations';

    /**
     * Relation managers on View pages are read-only by default; this one
     * must allow creating records from the client profile.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Repeater::make('items')
                ->relationship()
                ->schema([
                    TextInput::make('description')->required()->columnSpan(2),
                    TextInput::make('quantity')->numeric()->default(1)->required(),
                    TextInput::make('unit_price')->numeric()->prefix('৳')->required(),
                ])
                ->columns(4)
                ->minItems(1)
                ->required()
                ->columnSpanFull(),
            Grid::make(3)->schema([
                DatePicker::make('valid_until')->default(now()->addDays(14)),
                TextInput::make('discount')->numeric()->prefix('৳')->default(0),
                Textarea::make('notes')
                    ->rows(2)
                    ->helperText('Shown to the customer on the quotation page.'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->searchable()->copyable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('total')->money('BDT'),
                TextColumn::make('valid_until')->date(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('New quotation')
                    ->mutateDataUsing(function (array $data): array {
                        /** @var User $client */
                        $client = $this->getOwnerRecord();

                        $data['reference'] = Quotation::generateReference();
                        $data['token'] = str()->random(40);
                        $data['customer_name'] = $client->name;
                        $data['customer_email'] = $client->email;

                        return $data;
                    })
                    ->after(fn (Quotation $record) => app(QuotationService::class)->recalculateTotals($record))
                    ->successNotificationTitle('Quotation created — send it from the Quotations page when ready'),
            ]);
    }
}
