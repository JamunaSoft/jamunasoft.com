<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TldResource\Pages\ListTlds;
use App\Models\Tld;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class TldResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Tld::class;

    protected static string $permissionKey = 'domains';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyBangladeshi;

    protected static string|UnitEnum|null $navigationGroup = 'Domains';

    protected static ?string $navigationLabel = 'TLD Pricing';

    protected static ?string $modelLabel = 'TLD';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('tld')
                ->label('TLD')
                ->prefix('.')
                ->required()
                ->unique(ignoreRecord: true)
                ->helperText('Without the leading dot, e.g. com or com.bd')
                ->dehydrateStateUsing(fn (string $state) => strtolower(ltrim(trim($state), '.'))),
            Grid::make(3)->schema([
                TextInput::make('register_price')->numeric()->prefix('৳')->required()->label('Registration / year'),
                TextInput::make('renew_price')->numeric()->prefix('৳')->required()->label('Renewal / year'),
                TextInput::make('transfer_price')->numeric()->prefix('৳')->required()->label('Transfer'),
            ]),
            Grid::make(2)->schema([
                Toggle::make('is_active')->label('Active (shown on website)'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tld')
                    ->formatStateUsing(fn (string $state) => '.'.$state)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('register_price')->money('BDT')->sortable()->label('Register'),
                TextColumn::make('renew_price')->money('BDT')->sortable()->label('Renew'),
                TextColumn::make('transfer_price')->money('BDT')->sortable()->label('Transfer'),
                ToggleColumn::make('is_active')->label('Active'),
                TextColumn::make('sort_order')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTlds::route('/'),
        ];
    }
}
