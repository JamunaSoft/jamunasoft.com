<?php

namespace App\Filament\Client\Resources;

use App\Filament\Client\Resources\ClientServiceResource\Pages\ListClientServices;
use App\Models\ClientService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientServiceResource extends Resource
{
    protected static ?string $model = ClientService::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowPathRoundedSquare;

    protected static ?string $navigationLabel = 'My Services';

    protected static ?string $modelLabel = 'Service';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                TextColumn::make('domain')->placeholder('—'),
                TextColumn::make('billing_cycle')->badge()->color('gray'),
                TextColumn::make('price')->money('BDT'),
                TextColumn::make('next_due_at')->label('Next due')->date()->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('next_due_at')
            ->emptyStateHeading('No services yet')
            ->emptyStateDescription('Your hosting and other subscriptions will appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListClientServices::route('/'),
        ];
    }
}
