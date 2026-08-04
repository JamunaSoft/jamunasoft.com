<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TestimonialResource\Pages\ManageTestimonials;
use App\Filament\Support\Schemas;
use App\Models\Testimonial;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class TestimonialResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Testimonial::class;

    protected static string $permissionKey = 'testimonials';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 17;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('author_name')->required()->maxLength(255),
                TextInput::make('author_designation')->maxLength(255),
            ]),
            TextInput::make('company')->maxLength(255),
            Textarea::make('quote')->required()->rows(4),
            Grid::make(2)->schema([
                Select::make('rating')
                    ->options([5 => '5 stars', 4 => '4 stars', 3 => '3 stars', 2 => '2 stars', 1 => '1 star']),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            SpatieMediaLibraryFileUpload::make('avatar')
                ->collection('avatar')
                ->image()
                ->avatar()
                ->maxSize(2048),
            Grid::make(2)->schema([
                Toggle::make('is_approved')->label('Approved (visible on site)'),
                Toggle::make('is_featured')->label('Featured on home page'),
            ]),
            Schemas::bengaliSection(['long:quote' => 'Quote']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('author_name')->searchable()->sortable(),
                TextColumn::make('company')->searchable()->toggleable(),
                TextColumn::make('rating')->sortable(),
                IconColumn::make('is_approved')->boolean()->label('Approved'),
                IconColumn::make('is_featured')->boolean()->label('Featured'),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_approved'),
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
            'index' => ManageTestimonials::route('/'),
        ];
    }
}
