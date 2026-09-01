<?php

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Concerns\HasPermissionGates;
use App\Filament\Resources\CustomerResource\Pages\ListCustomers;
use App\Filament\Resources\CustomerResource\Pages\ViewCustomer;
use App\Filament\Resources\CustomerResource\RelationManagers\DomainOrdersRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\DomainsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\EmailLogsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\QuotationsRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\ServicesRelationManager;
use App\Filament\Resources\CustomerResource\RelationManagers\TicketsRelationManager;
use App\Mail\ClientWelcome;
use App\Models\EmailLog;
use App\Models\Payment;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use UnitEnum;

class CustomerResource extends Resource
{
    use HasPermissionGates;

    protected static ?string $model = User::class;

    protected static string $permissionKey = 'clients';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?string $modelLabel = 'Client';

    protected static ?int $navigationSort = 1;

    /**
     * Clients are users without any admin-panel role.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('roles')
            ->withCount(['domains', 'services'])
            ->withSum(['invoices as unpaid_total' => fn (Builder $query) => $query->unpaid()], 'total');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
            TextInput::make('secondary_email')
                ->label('Secondary email')
                ->email()
                ->helperText('Optional. Invoices, receipts and renewal reminders also go to this address; login stays on the primary email.'),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->dehydrated(fn (?string $state) => filled($state))
                ->visible(fn (string $operation) => $operation === 'edit')
                ->helperText('Leave blank to keep the current password. New clients receive a set-password link by email automatically.'),
            Grid::make(2)->schema([
                TextInput::make('company_name')
                    ->label('Company / organization')
                    ->helperText('Shown as the billed-to name on invoice PDFs.'),
                TextInput::make('phone'),
                TextInput::make('address')->columnSpan(2),
                TextInput::make('city'),
                TextInput::make('postal_code'),
                TextInput::make('country')->default('Bangladesh'),
            ])->columnSpanFull(),
            Textarea::make('admin_notes')
                ->label('Admin notes')
                ->rows(3)
                ->helperText('Staff-only. The client never sees this.')
                ->columnSpanFull(),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->schema([
                    Grid::make(['default' => 2, 'md' => 4])->schema([
                        TextEntry::make('lifetime_revenue')
                            ->label('Lifetime revenue')
                            ->state(fn (User $record) => Payment::where('user_id', $record->id)->sum('amount'))
                            ->money('BDT')
                            ->weight(FontWeight::Bold)
                            ->color('success'),
                        TextEntry::make('open_balance')
                            ->label('Open balance')
                            ->state(fn (User $record) => $record->invoices()->unpaid()->sum('total') - $record->invoices()->unpaid()->sum('amount_paid'))
                            ->money('BDT')
                            ->weight(FontWeight::Bold)
                            ->color(fn ($state) => (float) $state > 0 ? 'danger' : 'gray'),
                        TextEntry::make('invoices_summary')
                            ->label('Invoices')
                            ->badge()
                            ->color(fn (string $state) => str_contains($state, 'Unpaid') ? 'warning' : 'gray')
                            ->state(function (User $record): array {
                                $counts = $record->invoices()
                                    ->selectRaw('status, count(*) as aggregate')
                                    ->groupBy('status')
                                    ->pluck('aggregate', 'status');

                                return $counts->isEmpty()
                                    ? ['None']
                                    : $counts->map(fn ($count, $status) => $count.' '.InvoiceStatus::from($status)->getLabel())->values()->all();
                            }),
                        TextEntry::make('support_activity')
                            ->label('Open tickets · Emails sent')
                            ->state(fn (User $record) => $record->tickets()->awaitingStaff()->count().' · '.$record->emailLogs()->count()),
                    ]),
                ])
                ->columnSpanFull(),

            Section::make('Client information')
                ->columns(3)
                ->schema([
                    TextEntry::make('name'),
                    TextEntry::make('company_name')->label('Company')->placeholder('—'),
                    TextEntry::make('created_at')->label('Client since')->date(),
                    TextEntry::make('email')->copyable(),
                    TextEntry::make('secondary_email')->label('Secondary email')->copyable()->placeholder('—'),
                    TextEntry::make('phone')->placeholder('—'),
                    TextEntry::make('billing_address')
                        ->label('Billing address')
                        ->state(fn (User $record) => collect([
                            $record->address,
                            trim(($record->city ?? '').' '.($record->postal_code ?? '')),
                            $record->country,
                        ])->filter()->implode(', '))
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make('Admin notes')
                ->schema([
                    TextEntry::make('admin_notes')
                        ->hiddenLabel()
                        ->color('warning'),
                ])
                ->visible(fn (User $record) => filled($record->admin_notes))
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->copyable(),
                TextColumn::make('domains_count')->label('Domains')->sortable(),
                TextColumn::make('services_count')->label('Services')->sortable(),
                TextColumn::make('unpaid_total')
                    ->label('Unpaid')
                    ->money('BDT')
                    ->placeholder('—')
                    ->color(fn ($state) => (float) $state > 0 ? 'danger' : null)
                    ->sortable(),
                TextColumn::make('created_at')->label('Since')->date()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (User $record) => static::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * Email the client a link to set (or reset) their client-panel password.
     * Used on account creation and by the "Send password link" action.
     */
    public static function sendSetPasswordLink(User $user): void
    {
        $token = Password::createToken($user);
        $url = Filament::getPanel('client')->getResetPasswordUrl($token, $user);

        $mail = new ClientWelcome($user, $url);
        Mail::to($user->email)->queue($mail);

        EmailLog::create([
            'user_id' => $user->id,
            'type' => 'client_welcome',
            'subject' => $mail->envelope()->subject,
            'recipient' => $user->email,
            'status' => 'queued',
            'queued_at' => now(),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            DomainsRelationManager::class,
            ServicesRelationManager::class,
            InvoicesRelationManager::class,
            QuotationsRelationManager::class,
            DomainOrdersRelationManager::class,
            TicketsRelationManager::class,
            EmailLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCustomers::route('/'),
            'view' => ViewCustomer::route('/{record}'),
        ];
    }
}
