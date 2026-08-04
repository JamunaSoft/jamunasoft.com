<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\FaqResource\Pages\ManageFaqs;
use App\Filament\Support\Schemas;
use App\Models\Faq;
use App\Models\Service;
use App\Models\Solution;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\MorphToSelect;
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

class FaqResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Faq::class;

    protected static string $permissionKey = 'faqs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 19;

    protected static ?string $navigationLabel = 'FAQs';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('question')->required()->maxLength(500),
            Textarea::make('answer')->required()->rows(4),
            MorphToSelect::make('faqable')
                ->label('Attach to (optional)')
                ->types([
                    MorphToSelect\Type::make(Service::class)->titleAttribute('name'),
                    MorphToSelect\Type::make(Solution::class)->titleAttribute('name'),
                ]),
            Grid::make(3)->schema([
                Toggle::make('is_active')->default(true),
                Toggle::make('is_featured')->label('Show on home page'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            Schemas::bengaliSection(['long:question' => 'Question', 'long:answer' => 'Answer']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')->searchable()->wrap()->limit(80),
                TextColumn::make('faqable.name')->label('Attached to')->placeholder('General')->toggleable(),
                IconColumn::make('is_featured')->boolean()->label('Home'),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
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

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
