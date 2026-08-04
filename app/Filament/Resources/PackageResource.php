<?php

namespace App\Filament\Resources;

use App\Enums\PackageCategory;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\PackageResource\Pages\CreatePackage;
use App\Filament\Resources\PackageResource\Pages\EditPackage;
use App\Filament\Resources\PackageResource\Pages\ListPackages;
use App\Filament\Support\Schemas;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Illuminate\Support\Str;
use UnitEnum;

class PackageResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Package::class;

    protected static string $permissionKey = 'packages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 15;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, ?Package $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash']),
                ]),
                Grid::make(2)->schema([
                    Select::make('category')
                        ->options(PackageCategory::class)
                        ->required()
                        ->default(PackageCategory::Website),
                    Select::make('service_id')
                        ->relationship('service', 'name')
                        ->label('Related service')
                        ->preload(),
                ]),
                Textarea::make('excerpt')->rows(2)->maxLength(500),
            ]),

            Section::make('Pricing')->schema([
                Grid::make(3)->schema([
                    TextInput::make('price')
                        ->numeric()
                        ->prefix('৳')
                        ->label('Regular price'),
                    TextInput::make('discounted_price')
                        ->numeric()
                        ->prefix('৳'),
                    TextInput::make('price_suffix')
                        ->placeholder('/month, one-time, /year')
                        ->maxLength(30),
                ]),
                Toggle::make('is_starting_from')
                    ->label('Show as "Starting from"'),
            ]),

            Section::make('Features')->schema([
                Repeater::make('features')
                    ->label('Included features')
                    ->simple(TextInput::make('feature')->required())
                    ->defaultItems(0),
                Repeater::make('excluded_features')
                    ->label('Not included')
                    ->simple(TextInput::make('feature')->required())
                    ->defaultItems(0),
                Grid::make(2)->schema([
                    TextInput::make('delivery_time')->placeholder('e.g. 2–3 weeks'),
                    TextInput::make('support_period')->placeholder('e.g. 6 months free support'),
                ]),
            ]),

            Section::make('Call to action')->schema([
                Grid::make(2)->schema([
                    TextInput::make('cta_label')->placeholder('Get Started'),
                    TextInput::make('cta_url')
                        ->helperText('Leave blank to open the quotation form.'),
                ]),
            ])->collapsed(),

            Section::make('Publishing')->schema([
                Grid::make(4)->schema([
                    Toggle::make('is_active')->default(true),
                    Toggle::make('is_recommended')->label('Recommended badge'),
                    Toggle::make('is_featured')->label('Featured on home'),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
            ]),

            Schemas::bengaliSection([
                'name' => 'Name',
                'long:excerpt' => 'Excerpt',
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('price')->money('BDT')->sortable(),
                TextColumn::make('discounted_price')->money('BDT')->toggleable(),
                IconColumn::make('is_recommended')->boolean()->label('Recommended'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('category')->options(PackageCategory::class),
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
            'index' => ListPackages::route('/'),
            'create' => CreatePackage::route('/create'),
            'edit' => EditPackage::route('/{record}/edit'),
        ];
    }
}
