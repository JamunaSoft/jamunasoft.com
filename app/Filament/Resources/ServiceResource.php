<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\ServiceResource\Pages\CreateService;
use App\Filament\Resources\ServiceResource\Pages\EditService;
use App\Filament\Resources\ServiceResource\Pages\ListServices;
use App\Filament\Resources\ServiceResource\RelationManagers\FaqsRelationManager;
use App\Filament\Support\Schemas;
use App\Models\Service;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class ServiceResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Service::class;

    protected static string $permissionKey = 'services';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 10;

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
                        ->afterStateUpdated(fn ($state, callable $set, ?Service $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash'])
                        ->helperText('Used in the URL: /services/{slug}'),
                ]),
                Select::make('service_category_id')
                    ->relationship('category', 'name')
                    ->label('Category')
                    ->preload()
                    ->searchable(),
                Textarea::make('excerpt')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Short description shown on cards and listings.'),
                RichEditor::make('description')
                    ->columnSpanFull(),
                TextInput::make('icon')
                    ->helperText('Heroicon name shown on cards, e.g. globe-alt'),
            ]),

            Section::make('Media')->schema([
                SpatieMediaLibraryFileUpload::make('featured')
                    ->collection('featured')
                    ->image()
                    ->maxSize(4096)
                    ->label('Featured image'),
                SpatieMediaLibraryFileUpload::make('og')
                    ->collection('og')
                    ->image()
                    ->maxSize(4096)
                    ->label('Open Graph image (social sharing)'),
            ])->collapsed(),

            Section::make('Details')->schema([
                Repeater::make('benefits')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                Repeater::make('features')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                TagsInput::make('technologies')
                    ->placeholder('Add a technology, e.g. Laravel'),
                Repeater::make('process_steps')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
            ])->collapsed(),

            Section::make('Publishing')->schema([
                Grid::make(3)->schema([
                    Toggle::make('is_active')->default(true),
                    Toggle::make('is_featured')->label('Featured on home page'),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
            ]),

            Schemas::bengaliSection([
                'name' => 'Name',
                'long:excerpt' => 'Excerpt',
                'long:description' => 'Description (HTML allowed)',
            ]),

            Schemas::seoSection(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('featured')
                    ->collection('featured')
                    ->conversion('card')
                    ->label(''),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category.name')->sortable()->toggleable(),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')->dateTime()->sortable()->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('service_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                TernaryFilter::make('is_active'),
                TernaryFilter::make('is_featured'),
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

    public static function getRelations(): array
    {
        return [
            FaqsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListServices::route('/'),
            'create' => CreateService::route('/create'),
            'edit' => EditService::route('/{record}/edit'),
        ];
    }
}
