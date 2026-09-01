<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\DomainOrderType;
use App\Models\DomainOrder;
use App\Models\User;
use App\Services\DomainOrderService;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DomainOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'domainOrders';

    protected static ?string $title = 'Domain Orders';

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
                Select::make('type')
                    ->options(DomainOrderType::class)
                    ->default(DomainOrderType::Register)
                    ->required(),
                TextInput::make('domain_name')
                    ->required()
                    ->placeholder('example.com')
                    ->dehydrateStateUsing(fn (string $state) => strtolower(trim($state))),
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('domain_name'),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('amount')->money('BDT'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('New domain order')
                    ->using(function (array $data): DomainOrder {
                        /** @var User $client */
                        $client = $this->getOwnerRecord();

                        try {
                            return app(DomainOrderService::class)->create(
                                customer: [
                                    'name' => $client->name,
                                    'email' => $client->email,
                                    'phone' => $client->phone,
                                    'user_id' => $client->id,
                                ],
                                domainName: $data['domain_name'],
                                type: DomainOrderType::from(is_string($data['type']) ? $data['type'] : $data['type']->value),
                                years: (int) $data['years'],
                                amount: filled($data['amount'] ?? null) ? (float) $data['amount'] : null,
                            );
                        } catch (\InvalidArgumentException $e) {
                            Notification::make()->title($e->getMessage())->danger()->send();

                            throw new Halt;
                        }
                    }),
            ]);
    }
}
