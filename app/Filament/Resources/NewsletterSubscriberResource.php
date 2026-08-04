<?php

namespace App\Filament\Resources;

use App\Enums\NewsletterStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\NewsletterSubscriberResource\Pages\ManageNewsletterSubscribers;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use UnitEnum;

class NewsletterSubscriberResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = NewsletterSubscriber::class;

    protected static string $permissionKey = 'newsletter';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Leads';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationLabel = 'Newsletter';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->email()
                ->required()
                ->unique(ignoreRecord: true),
            Select::make('status')
                ->options(NewsletterStatus::class)
                ->default(NewsletterStatus::Subscribed)
                ->required(),
            TextInput::make('source')->placeholder('e.g. footer form'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->searchable()->sortable()->copyable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('source')->toggleable(),
                TextColumn::make('confirmed_at')->dateTime()->toggleable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(NewsletterStatus::class),
            ])
            ->recordActions([
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('export')
                        ->label('Export CSV')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->action(fn (Collection $records) => static::exportCsv($records)),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function exportCsv(Collection $records)
    {
        return Response::streamDownload(function () use ($records) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Email', 'Status', 'Source', 'Confirmed at', 'Subscribed at']);

            foreach ($records as $subscriber) {
                fputcsv($out, [
                    $subscriber->email,
                    $subscriber->status->value,
                    $subscriber->source,
                    $subscriber->confirmed_at?->toDateTimeString(),
                    $subscriber->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNewsletterSubscribers::route('/'),
        ];
    }
}
