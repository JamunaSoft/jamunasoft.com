<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\DomainOrdersRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\ServicesRelationManager;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CustomerResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = User::class;

    protected static string $permissionKey = 'clients';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?int $navigationSort = 1;

    /**
     * Clients are users without any admin-panel role.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('roles')
            ->withCount(['domains', 'services'])
            ->withSum(['invoices as unpaid_total' => fn (Builder $query) => $query->unpaid()], 'total');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrated(fn (?string $state) => filled($state))
                ->required(fn (string $operation) => $operation === 'create')
                ->helperText('The client can also set it themselves via "Forgot password" on the client panel.'),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('created_at')->label('Client since')->date(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('domains_count')->label('Domains')->sortable(),
                TextColumn::make('services_count')->label('Services')->sortable(),
                TextColumn::make('unpaid_total')
                    ->label('Unpaid')
                    ->money('BDT')
                    ->placeholder('—')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('created_at')->label('Since')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
            ServicesRelationManager::class,
            InvoicesRelationManager::class,
            DomainOrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
