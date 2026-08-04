<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadActivityType;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ActivitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'activities';

    protected static ?string $title = 'Activity & Follow-up History';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('type')
                ->options(LeadActivityType::class)
                ->default(LeadActivityType::Note)
                ->required(),
            Textarea::make('body')
                ->label('Details')
                ->required()
                ->rows(3),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')->badge(),
                TextColumn::make('body')->wrap()->limit(120),
                TextColumn::make('user.name')->label('By')->placeholder('System'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                DeleteAction::make(),
            ]);
    }
}
