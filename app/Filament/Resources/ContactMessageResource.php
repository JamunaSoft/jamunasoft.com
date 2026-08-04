<?php

namespace App\Filament\Resources;

use App\Enums\ContactMessageStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\ContactMessageResource\Pages\ListContactMessages;
use App\Models\ContactMessage;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class ContactMessageResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = ContactMessage::class;

    protected static string $permissionKey = 'contact-messages';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Leads';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::where('status', ContactMessageStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextEntry::make('name'),
                TextEntry::make('email')->copyable(),
                TextEntry::make('phone')->placeholder('—'),
                TextEntry::make('company')->placeholder('—'),
                TextEntry::make('service.name')->label('Interested service')->placeholder('—'),
                TextEntry::make('subject')->placeholder('—'),
                TextEntry::make('status')->badge(),
                TextEntry::make('created_at')->dateTime(),
            ]),
            TextEntry::make('message')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('subject')->limit(40)->toggleable(),
                TextColumn::make('service.name')->toggleable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(ContactMessageStatus::class),
            ])
            ->recordActions([
                ViewAction::make()
                    ->after(function (ContactMessage $record) {
                        if ($record->status === ContactMessageStatus::New) {
                            $record->update(['status' => ContactMessageStatus::Read]);
                        }
                    }),
                Action::make('markReplied')
                    ->label('Mark replied')
                    ->icon(Heroicon::OutlinedCheck)
                    ->visible(fn (ContactMessage $record) => $record->status !== ContactMessageStatus::Replied)
                    ->action(fn (ContactMessage $record) => $record->update(['status' => ContactMessageStatus::Replied])),
                Action::make('attachment')
                    ->label('Attachment')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->visible(fn (ContactMessage $record) => filled($record->attachment_path))
                    ->action(fn (ContactMessage $record) => Storage::download($record->attachment_path)),
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
            'index' => ListContactMessages::route('/'),
        ];
    }
}
