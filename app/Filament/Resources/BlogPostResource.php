<?php

namespace App\Filament\Resources;

use App\Enums\PublishStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\BlogPostResource\Pages\CreateBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\EditBlogPost;
use App\Filament\Resources\BlogPostResource\Pages\ListBlogPosts;
use App\Filament\Support\Schemas;
use App\Models\BlogPost;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class BlogPostResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = BlogPost::class;

    protected static string $permissionKey = 'blog';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNewspaper;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Post')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, ?BlogPost $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash']),
                ]),
                Grid::make(3)->schema([
                    Select::make('blog_category_id')
                        ->relationship('category', 'name')
                        ->label('Category')
                        ->preload(),
                    Select::make('user_id')
                        ->relationship('author', 'name')
                        ->label('Author')
                        ->default(fn () => auth()->id())
                        ->preload(),
                    Select::make('tags')
                        ->relationship('tags', 'name')
                        ->multiple()
                        ->preload(),
                ]),
                Textarea::make('excerpt')->rows(2)->maxLength(500),
                RichEditor::make('content')->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('featured')
                    ->collection('featured')
                    ->image()
                    ->maxSize(4096)
                    ->label('Featured image'),
            ]),

            Section::make('Publishing')->schema([
                Grid::make(3)->schema([
                    Select::make('status')
                        ->options(PublishStatus::class)
                        ->default(PublishStatus::Draft)
                        ->required(),
                    DateTimePicker::make('published_at')
                        ->label('Publish date')
                        ->helperText('A future date schedules the post.'),
                    Toggle::make('is_featured')->label('Featured post'),
                ]),
            ]),

            Schemas::bengaliSection([
                'title' => 'Title',
                'long:excerpt' => 'Excerpt',
                'long:content' => 'Content (HTML allowed)',
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
                TextColumn::make('title')->searchable()->sortable()->limit(50),
                TextColumn::make('category.name')->sortable()->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('published_at')->dateTime()->sortable(),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
                TextColumn::make('views')->sortable()->toggleable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('blog_category_id')
                    ->relationship('category', 'name')
                    ->label('Category'),
                SelectFilter::make('status')->options(PublishStatus::class),
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
            'index' => ListBlogPosts::route('/'),
            'create' => CreateBlogPost::route('/create'),
            'edit' => EditBlogPost::route('/{record}/edit'),
        ];
    }
}
