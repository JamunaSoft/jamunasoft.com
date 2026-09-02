<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * WHMCS-style billing contacts: extra companies this one client (one
 * login, one owner) can be invoiced under.
 */
class BillingProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'billingProfiles';

    protected static ?string $title = 'Billing Profiles';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('company_name')->required(),
                TextInput::make('contact_name')->helperText('Leave empty to use the client\'s own name.'),
                TextInput::make('email')
                    ->email()
                    ->helperText('Optional. This profile\'s invoices are ALSO emailed here — and go in a separate email instead of the owner\'s bundle.'),
                TextInput::make('phone'),
                TextInput::make('address')->columnSpan(2),
                TextInput::make('city'),
                TextInput::make('postal_code'),
                TextInput::make('country')->default('Bangladesh'),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('company_name')
            ->columns([
                TextColumn::make('company_name')->searchable(),
                TextColumn::make('contact_name')->placeholder('—'),
                TextColumn::make('email')->placeholder('—'),
                TextColumn::make('phone')->placeholder('—'),
                TextColumn::make('invoices_count')->counts('invoices')->label('Invoices'),
            ])
            ->headerActions([
                CreateAction::make()->label('New profile'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No extra billing profiles')
            ->emptyStateDescription('The client\'s own name and address are used by default. Add profiles when one owner is invoiced under multiple companies.');
    }
}
