<?php

namespace App\Filament\Client\Resources;

use App\Enums\DomainOrderStatus;
use App\Filament\Client\Resources\DomainOrderResource\Pages\ListDomainOrders;
use App\Models\DomainOrder;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DomainOrderResource extends Resource
{
    protected static ?string $model = DomainOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingCart;

    protected static ?string $navigationLabel = 'My Orders';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('status', DomainOrderStatus::PendingPayment)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('reference')->copyable(),
                TextEntry::make('status')->badge(),
                TextEntry::make('domain_name'),
                TextEntry::make('type')->badge()->color('gray'),
                TextEntry::make('years'),
                TextEntry::make('amount')->money('BDT'),
                TextEntry::make('created_at')->dateTime(),
                TextEntry::make('completed_at')->dateTime()->placeholder('—'),
            ]),
            TextEntry::make('payment_instructions')
                ->state(fn () => (string) settings('domain_payment_instructions', ''))
                ->visible(fn (DomainOrder $record) => $record->status === DomainOrderStatus::PendingPayment
                    && filled(settings('domain_payment_instructions')))
                ->helperText(fn (DomainOrder $record) => 'Please mention your order reference '.$record->reference.' with the payment.')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable(),
                TextColumn::make('domain_name')->searchable(),
                TextColumn::make('type')->badge()->color('gray'),
                TextColumn::make('amount')->money('BDT'),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
            ])
            ->emptyStateHeading('No orders yet')
            ->emptyStateDescription('Your domain orders will appear here.');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDomainOrders::route('/'),
        ];
    }
}
