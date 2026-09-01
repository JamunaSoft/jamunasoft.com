<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

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
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->placeholder('Web Hosting — Basic')
                    ->helperText('What appears on invoices.'),
                Select::make('hosting_plan_id')
                    ->label('Hosting plan (optional)')
                    ->relationship('hostingPlan', 'name')
                    ->placeholder('Custom service'),
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('billing_cycle')->badge()->color('gray'),
                TextColumn::make('price')->money('BDT'),
                TextColumn::make('next_due_at')->label('Next due')->date()->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->paginated(false)
            ->headerActions([
                CreateAction::make()->label('New service'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
