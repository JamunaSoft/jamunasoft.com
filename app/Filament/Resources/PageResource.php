<?php

namespace App\Filament\Resources;

use App\Enums\PageTemplate;
use App\Enums\PublishStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\PageResource\Pages\CreatePage;
use App\Filament\Resources\PageResource\Pages\EditPage;
use App\Filament\Resources\PageResource\Pages\ListPages;
use App\Filament\Support\Schemas;
use App\Models\Page;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PageResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Page::class;

    protected static string $permissionKey = 'pages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|UnitEnum|null $navigationGroup = 'Website';

    protected static ?int $navigationSort = 30;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Page')->schema([
                Grid::make(2)->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn ($state, callable $set, ?Page $record) => $record === null ? $set('slug', Str::slug((string) $state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->rules(['alpha_dash'])
                        ->helperText('Used in the URL: /page/{slug}'),
                ]),
                Grid::make(2)->schema([
                    Select::make('template')
                        ->options(PageTemplate::class)
                        ->default(PageTemplate::Standard)
                        ->required(),
                    Select::make('status')
                        ->options(PublishStatus::class)
                        ->default(PublishStatus::Draft)
                        ->required(),
                ]),
                RichEditor::make('content')
                    ->label('Main content')
                    ->helperText('Used by standard and policy templates. Landing pages can rely on sections below.')
                    ->columnSpanFull(),
            ]),

            Section::make('Sections')
                ->description('Structured content blocks rendered in order.')
                ->schema([
                    Builder::make('sections')
                        ->label('')
                        ->blocks(static::sectionBlocks())
                        ->collapsible()
                        ->blockNumbers(false),
                ])->collapsed(),

            Schemas::bengaliSection([
                'title' => 'Title',
                'long:content' => 'Main content (HTML allowed)',
            ]),

            Schemas::seoSection(),
        ]);
    }

    /** @return array<Block> */
    protected static function sectionBlocks(): array
    {
        $heading = fn () => TextInput::make('heading')->required();
        $subheading = fn () => Textarea::make('subheading')->rows(2);

        return [
            Block::make('hero')->schema([
                $heading(),
                $subheading(),
                TextInput::make('cta_label'),
                TextInput::make('cta_url'),
                FileUpload::make('image')->image()->directory('pages')->maxSize(4096),
            ]),
            Block::make('rich_text')->schema([
                RichEditor::make('content')->required(),
            ]),
            Block::make('image_text')->schema([
                $heading(),
                RichEditor::make('content'),
                FileUpload::make('image')->image()->directory('pages')->maxSize(4096),
                Select::make('image_position')->options(['left' => 'Left', 'right' => 'Right'])->default('right'),
            ]),
            Block::make('feature_grid')->schema([
                $heading(),
                $subheading(),
                Repeater::make('features')->schema([
                    TextInput::make('title')->required(),
                    Textarea::make('description')->rows(2),
                    TextInput::make('icon')->helperText('Heroicon name'),
                ])->defaultItems(0),
            ]),
            Block::make('stats')->schema([
                Repeater::make('stats')->schema([
                    TextInput::make('value')->required(),
                    TextInput::make('label')->required(),
                ])->defaultItems(0),
            ]),
            Block::make('logo_grid')->schema([
                $heading(),
                FileUpload::make('logos')->image()->multiple()->directory('pages/logos')->maxSize(2048),
            ]),
            Block::make('cta')->schema([
                $heading(),
                $subheading(),
                TextInput::make('cta_label'),
                TextInput::make('cta_url'),
            ]),
            Block::make('faq')->schema([
                $heading(),
                Repeater::make('items')->schema([
                    TextInput::make('question')->required(),
                    Textarea::make('answer')->required()->rows(3),
                ])->defaultItems(0),
            ]),
            Block::make('testimonials')->schema([
                $heading(),
                TextInput::make('count')->label('Number of items')->numeric()->default(6),
            ]),
            Block::make('portfolio_grid')->schema([
                $heading(),
                TextInput::make('count')->label('Number of items')->numeric()->default(6),
            ]),
            Block::make('service_grid')->schema([
                $heading(),
                TextInput::make('count')->label('Number of items')->numeric()->default(6),
            ]),
            Block::make('pricing_grid')->schema([
                $heading(),
                Select::make('category')->options([
                    'website' => 'Website',
                    'ecommerce' => 'E-commerce',
                    'software' => 'Custom Software',
                    'maintenance' => 'Maintenance',
                    'marketing' => 'Digital Marketing',
                    'social_media' => 'Social Media',
                    'hosting' => 'Hosting & Email',
                ]),
            ]),
            Block::make('contact_form')->schema([
                $heading(),
                $subheading(),
            ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('template')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                SelectFilter::make('template')->options(PageTemplate::class),
                SelectFilter::make('status')->options(PublishStatus::class),
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
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
