<?php

namespace App\Filament\Resources;

use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\TeamMemberResource\Pages\ManageTeamMembers;
use App\Filament\Support\Schemas;
use App\Models\TeamMember;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class TeamMemberResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = TeamMember::class;

    protected static string $permissionKey = 'team';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?int $navigationSort = 18;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')->required()->maxLength(255),
                TextInput::make('designation')->maxLength(255),
            ]),
            Textarea::make('bio')->rows(3),
            SpatieMediaLibraryFileUpload::make('photo')
                ->collection('photo')
                ->image()
                ->maxSize(2048),
            Grid::make(2)->schema([
                TextInput::make('email')->email(),
                TextInput::make('phone'),
                TextInput::make('linkedin_url')->url()->label('LinkedIn'),
                TextInput::make('facebook_url')->url()->label('Facebook'),
                TextInput::make('website_url')->url()->label('Website'),
                TextInput::make('sort_order')->numeric()->default(0),
            ]),
            Toggle::make('is_active')->default(true),
            Schemas::bengaliSection(['name' => 'Name', 'designation' => 'Designation', 'long:bio' => 'Biography']),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('photo')
                    ->collection('photo')
                    ->conversion('card')
                    ->circular()
                    ->label(''),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('designation')->searchable(),
                IconColumn::make('is_active')->boolean(),
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
            'index' => ManageTeamMembers::route('/'),
        ];
    }
}
