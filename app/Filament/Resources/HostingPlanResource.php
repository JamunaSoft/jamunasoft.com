<?php

namespace App\Filament\Resources;

use App\Enums\HostingPlanType;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\HostingPlanResource\Pages\CreateHostingPlan;
use App\Filament\Resources\HostingPlanResource\Pages\EditHostingPlan;
use App\Filament\Resources\HostingPlanResource\Pages\ListHostingPlans;
use App\Filament\Support\Schemas;
use App\Models\HostingPlan;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class HostingPlanResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = HostingPlan::class;

    protected static string $permissionKey = 'hosting';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedServerStack;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 16;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Plan')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('type')
                        ->options(HostingPlanType::class)
                        ->required()
                        ->default(HostingPlanType::Shared),
                ]),
                Grid::make(3)->schema([
                    TextInput::make('monthly_price')->numeric()->prefix('৳'),
                    TextInput::make('yearly_price')->numeric()->prefix('৳'),
                    TextInput::make('discounted_price')
                        ->numeric()
                        ->prefix('৳')
                        ->helperText('Discounted yearly price (optional)'),
                ]),
            ]),

            Section::make('Specifications')->schema([
                Grid::make(3)->schema([
                    TextInput::make('storage')->placeholder('10 GB NVMe'),
                    TextInput::make('bandwidth')->placeholder('Unmetered'),
                    TextInput::make('websites')->placeholder('1 website'),
                    TextInput::make('email_accounts')->placeholder('10 accounts'),
                    TextInput::make('databases')->placeholder('5 databases'),
                    TextInput::make('backup_frequency')->placeholder('Daily'),
                ]),
                Grid::make(2)->schema([
                    Toggle::make('has_ssl')->label('Free SSL included')->default(true),
                    TextInput::make('support_level')->placeholder('24/7 priority support'),
                ]),
                Repeater::make('features')
                    ->label('Additional features')
                    ->simple(TextInput::make('feature')->required())
                    ->defaultItems(0),
            ]),

            Section::make('Publishing')->schema([
                Grid::make(3)->schema([
                    Toggle::make('is_active')->default(true),
                    Toggle::make('is_recommended')->label('Recommended badge'),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
            ]),

            Schemas::bengaliSection(['name' => 'Name']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('type')->badge()->sortable(),
                TextColumn::make('monthly_price')->money('BDT')->sortable(),
                TextColumn::make('yearly_price')->money('BDT')->toggleable(),
                TextColumn::make('storage')->toggleable(),
                IconColumn::make('is_recommended')->boolean()->label('Recommended'),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('type')->options(HostingPlanType::class),
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
            'index' => ListHostingPlans::route('/'),
            'create' => CreateHostingPlan::route('/create'),
            'edit' => EditHostingPlan::route('/{record}/edit'),
        ];
    }
}
