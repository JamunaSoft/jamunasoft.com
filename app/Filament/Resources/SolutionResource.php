<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\SolutionResource\Pages\CreateSolution;
use App\Filament\Resources\SolutionResource\Pages\EditSolution;
use App\Filament\Resources\SolutionResource\Pages\ListSolutions;
use App\Filament\Support\Schemas;
use App\Models\Solution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
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
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class SolutionResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Solution::class;

    protected static string $permissionKey = 'solutions';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 12;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Industry Solutions';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Basics')->schema([
                Grid::make(2)->schema([
                    TextInput::make('name')
                        ->label('Industry name')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, ?Solution $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash'])
                        ->helperText('Used in the URL: /solutions/{slug}'),
                ]),
                Textarea::make('excerpt')->rows(2)->maxLength(500),
                RichEditor::make('description')->columnSpanFull(),
                TextInput::make('icon')->helperText('Heroicon name, e.g. academic-cap'),
                SpatieMediaLibraryFileUpload::make('featured')
                    ->collection('featured')
                    ->image()
                    ->maxSize(4096)
                    ->label('Featured image'),
            ]),

            Section::make('Content')->schema([
                Repeater::make('challenges')
                    ->label('Business challenges')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                Repeater::make('offerings')
                    ->label('Our solutions')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                Repeater::make('benefits')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                Select::make('services')
                    ->label('Recommended services')
                    ->relationship('services', 'name')
                    ->multiple()
                    ->preload(),
            ])->collapsed(),

            Section::make('Publishing')->schema([
                Grid::make(3)->schema([
                    Toggle::make('is_active')->default(true),
                    Toggle::make('is_featured')->label('Featured on home page'),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
            ]),

            Schemas::bengaliSection([
                'name' => 'Industry name',
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('services_count')->counts('services')->label('Services'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('sort_order')->sortable(),
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
            'index' => ListSolutions::route('/'),
            'create' => CreateSolution::route('/create'),
            'edit' => EditSolution::route('/{record}/edit'),
        ];
    }
}
