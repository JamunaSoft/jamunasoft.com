<?php

namespace App\Filament\Resources;

use App\Enums\LeadPriority;
use App\Enums\LeadStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\LeadResource\Pages\CreateLead;
use App\Filament\Resources\LeadResource\Pages\EditLead;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Filament\Resources\LeadResource\RelationManagers\ActivitiesRelationManager;
use App\Models\Lead;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use UnitEnum;

class LeadResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = Lead::class;

    protected static string $permissionKey = 'leads';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;

    protected static string|UnitEnum|null $navigationGroup = 'Sales & Leads';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationBadge(): ?string
    {
        $count = Lead::where('status', LeadStatus::New)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Contact')->schema([
                Grid::make(2)->schema([
                    TextInput::make('reference')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Generated automatically.'),
                    TextInput::make('name')->required()->maxLength(255),
                    TextInput::make('company')->maxLength(255),
                    TextInput::make('phone')->maxLength(50),
                    TextInput::make('email')->email(),
                    Select::make('preferred_contact')->options([
                        'phone' => 'Phone',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                    ]),
                ]),
            ]),

            Section::make('Project')->schema([
                Grid::make(2)->schema([
                    Select::make('service_id')
                        ->relationship('service', 'name')
                        ->label('Service')
                        ->preload()
                        ->searchable(),
                    TextInput::make('project_type'),
                    TextInput::make('existing_url')->label('Existing website'),
                    TextInput::make('budget'),
                    TextInput::make('timeline'),
                    TextInput::make('referral_source')->label('How they found us'),
                ]),
                Textarea::make('message')->label('Project description')->rows(4),
                TagsInput::make('required_features'),
            ]),

            Section::make('Management')->schema([
                Grid::make(2)->schema([
                    Select::make('status')
                        ->options(LeadStatus::class)
                        ->default(LeadStatus::New)
                        ->required(),
                    Select::make('priority')
                        ->options(LeadPriority::class)
                        ->default(LeadPriority::Normal)
                        ->required(),
                    Select::make('assigned_to')
                        ->relationship('assignee', 'name')
                        ->label('Assigned to')
                        ->preload(),
                    DateTimePicker::make('next_follow_up_at')->label('Next follow-up'),
                    DateTimePicker::make('last_contacted_at')->label('Last contacted'),
                ]),
                Textarea::make('internal_notes')
                    ->rows(3)
                    ->helperText('Private notes — never shown to the client.'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference')->searchable()->sortable()->copyable(),
                TextColumn::make('name')->searchable()->sortable()->description(fn (Lead $record) => $record->company),
                TextColumn::make('service.name')->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                TextColumn::make('priority')->badge()->sortable(),
                TextColumn::make('assignee.name')->label('Assigned')->placeholder('—')->toggleable(),
                TextColumn::make('next_follow_up_at')
                    ->label('Follow-up')
                    ->dateTime()
                    ->sortable()
                    ->color(fn (Lead $record) => $record->next_follow_up_at?->isPast() ? 'danger' : null),
                TextColumn::make('source')->badge()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options(LeadStatus::class)->multiple(),
                SelectFilter::make('priority')->options(LeadPriority::class),
                SelectFilter::make('assigned_to')->relationship('assignee', 'name')->label('Assigned to'),
                SelectFilter::make('service_id')->relationship('service', 'name')->label('Service'),
                SelectFilter::make('source')->options([
                    'quotation_form' => 'Quotation form',
                    'contact_form' => 'Contact form',
                    'manual' => 'Manual',
                ]),
                Filter::make('overdue')
                    ->label('Overdue follow-up')
                    ->query(fn (Builder $query) => $query->overdueFollowUp()),
                Filter::make('created_from')
                    ->schema([DateTimePicker::make('created_from')->label('Created from')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['created_from'] ?? null, fn ($q, $date) => $q->where('created_at', '>=', $date))),
                Filter::make('created_until')
                    ->schema([DateTimePicker::make('created_until')->label('Created until')])
                    ->query(fn (Builder $query, array $data) => $query->when($data['created_until'] ?? null, fn ($q, $date) => $q->where('created_at', '<=', $date))),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('attachment')
                    ->label('Attachment')
                    ->icon(Heroicon::OutlinedPaperClip)
                    ->visible(fn (Lead $record) => filled($record->attachment_path))
                    ->action(fn (Lead $record) => Storage::download($record->attachment_path)),
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
            fputcsv($out, ['Reference', 'Name', 'Company', 'Phone', 'Email', 'Service', 'Budget', 'Status', 'Priority', 'Source', 'Created']);

            foreach ($records as $lead) {
                fputcsv($out, [
                    $lead->reference, $lead->name, $lead->company, $lead->phone, $lead->email,
                    $lead->service?->name, $lead->budget, $lead->status->value,
                    $lead->priority->value, $lead->source, $lead->created_at?->toDateTimeString(),
                ]);
            }

            fclose($out);
        }, 'leads-'.now()->format('Y-m-d').'.csv');
    }

    public static function getRelations(): array
    {
        return [
            ActivitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLeads::route('/'),
            'create' => CreateLead::route('/create'),
            'edit' => EditLead::route('/{record}/edit'),
        ];
    }
}
