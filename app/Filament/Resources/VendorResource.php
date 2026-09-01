<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\VendorResource\Pages\ListVendors;
use App\Filament\Resources\VendorResource\Pages\ViewVendor;
use App\Filament\Resources\VendorResource\RelationManagers\ExpensesRelationManager;
use App\Models\Vendor;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
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

class VendorResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Vendor::class;

    protected static string $permissionKey = 'billing';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('expenses')
            ->withSum('expenses as total_paid', 'amount')
            ->withMax('expenses as last_paid_at', 'expensed_at');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required()->unique(ignoreRecord: true),
            TextInput::make('phone'),
            TextInput::make('email')->email(),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(4)->schema([
                TextEntry::make('name'),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('total_paid')
                    ->label('Total paid')
                    ->state(fn (Vendor $record) => $record->expenses()->sum('amount'))
                    ->money('BDT')
                    ->color('danger'),
                TextEntry::make('expenses_total')
                    ->label('Payments made')
                    ->state(fn (Vendor $record) => $record->expenses()->count()),
            ]),
            TextEntry::make('notes')->placeholder('—')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('phone')->placeholder('—')->toggleable(),
                TextColumn::make('expenses_count')->label('Payments')->sortable(),
                TextColumn::make('total_paid')->label('Total paid')->money('BDT')->placeholder('—')->sortable(),
                TextColumn::make('last_paid_at')->label('Last payment')->date()->placeholder('—')->sortable(),
            ])
            ->defaultSort('total_paid', 'desc')
            ->recordUrl(fn (Vendor $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ExpensesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListVendors::route('/'),
            'view' => ViewVendor::route('/{record}'),
        ];
    }
}
