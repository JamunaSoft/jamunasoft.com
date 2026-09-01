<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\PortfolioResource\Pages\CreatePortfolio;
use App\Filament\Resources\PortfolioResource\Pages\EditPortfolio;
use App\Filament\Resources\PortfolioResource\Pages\ListPortfolios;
use App\Filament\Support\Schemas;
use App\Models\Portfolio;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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

class PortfolioResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Portfolio::class;

    protected static string $permissionKey = 'portfolio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 13;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Portfolio Projects';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, ?Portfolio $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash']),
                ]),
                Grid::make(3)->schema([
                    Select::make('portfolio_category_id')
                        ->relationship('category', 'name')
                        ->label('Category')
                        ->preload(),
                    TextInput::make('client_name'),
                    TextInput::make('industry'),
                ]),
                Textarea::make('summary')
                    ->rows(2)
                    ->maxLength(500)
                    ->helperText('Short project summary for cards and listings.'),
                Grid::make(2)->schema([
                    TextInput::make('project_url')
                        ->url()
                        ->helperText('Public project URL. Never include staging or internal URLs.'),
                    TextInput::make('video_url')
                        ->label('Video URL (YouTube/Vimeo)')
                        ->url()
                        ->placeholder('https://www.youtube.com/watch?v=...')
                        ->helperText('For motion graphics: the player is embedded on the case-study page and cards get a play badge. Upload videos to YouTube, not the server.'),
                    DatePicker::make('completed_at')->label('Completion date'),
                ]),
            ]),

            Section::make('Case study')->schema([
                RichEditor::make('challenge')->label('Client challenge'),
                RichEditor::make('solution')->label('Proposed solution'),
                Repeater::make('key_features')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                TagsInput::make('technologies'),
                Repeater::make('results')
                    ->label('Results / outcomes')
                    ->schema([
                        TextInput::make('title')->required(),
                        Textarea::make('description')->rows(2),
                    ])
                    ->collapsible()
                    ->defaultItems(0),
                Select::make('services')
                    ->label('Services provided')
                    ->relationship('services', 'name')
                    ->multiple()
                    ->preload(),
                Textarea::make('testimonial_quote')->rows(2),
                TextInput::make('testimonial_author'),
            ])->collapsed(),

            Section::make('Media')->schema([
                SpatieMediaLibraryFileUpload::make('featured')
                    ->collection('featured')
                    ->image()
                    ->maxSize(4096)
                    ->label('Featured image'),
                SpatieMediaLibraryFileUpload::make('gallery')
                    ->collection('gallery')
                    ->image()
                    ->multiple()
                    ->reorderable()
                    ->maxSize(4096)
                    ->maxFiles(12)
                    ->label('Gallery / screenshots'),
                SpatieMediaLibraryFileUpload::make('client_logo')
                    ->collection('client_logo')
                    ->image()
                    ->maxSize(2048)
                    ->label('Client logo'),
            ])->collapsed(),

            Section::make('Publishing')->schema([
                Grid::make(3)->schema([
                    Toggle::make('is_active')->label('Published')->default(true),
                    Toggle::make('is_featured')->label('Featured on home page'),
                    TextInput::make('sort_order')->numeric()->default(0),
                ]),
            ]),

            Schemas::bengaliSection([
                'title' => 'Title',
                'long:summary' => 'Summary',
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
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('category.name')->sortable()->toggleable(),
                TextColumn::make('client_name')->searchable()->toggleable(),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                IconColumn::make('is_active')->boolean()->label('Published'),
                TextColumn::make('completed_at')->date()->sortable()->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('portfolio_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                TernaryFilter::make('is_active')->label('Published'),
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

    public static function getPages(): array
    {
        return [
            'index' => ListPortfolios::route('/'),
            'create' => CreatePortfolio::route('/create'),
            'edit' => EditPortfolio::route('/{record}/edit'),
        ];
    }
}
