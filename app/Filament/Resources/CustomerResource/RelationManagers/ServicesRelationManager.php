<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Enums\BillingCycle;
use App\Enums\ClientServiceStatus;
use App\Models\User;
use App\Services\RecurringBillingService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'services';

    /**
     * Relation managers on View pages are read-only by default; this one
     * must allow creating records from the client profile.
     */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(2)->schema([
                TextInput::make('name')
                    ->required()
                    ->placeholder('Web Hosting — Basic')
                    ->helperText('What appears on invoices.'),
                Select::make('hosting_plan_id')
                    ->label('Hosting plan (optional)')
                    ->relationship('hostingPlan', 'name')
                    ->placeholder('Custom service'),
                TextInput::make('domain')->placeholder('example.com'),
                Textarea::make('description')
                    ->rows(4)
                    ->placeholder("Machine Type: KVM\nCPU Platform: AMD, 4 vCore\nMemory up to 4GB")
                    ->helperText('Optional specs shown under the title on invoices — one line per row. Domain and billing period are added automatically.')
                    ->columnSpanFull(),
                Select::make('billing_cycle')
                    ->options(BillingCycle::class)
                    ->default(BillingCycle::Yearly)
                    ->required(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('৳')
                    ->required()
                    ->helperText('Per billing cycle.'),
                DatePicker::make('next_due_at')
                    ->label('Next due date')
                    ->required(),
                Select::make('status')
                    ->options(ClientServiceStatus::class)
                    ->default(ClientServiceStatus::Active)
                    ->required(),
            ]),
            Textarea::make('notes')->rows(2)->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('billing_cycle')->badge()->color('gray'),
                TextColumn::make('price')->money('BDT'),
                TextColumn::make('next_due_at')->label('Next due')->date()->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('invoiceAll')
                    ->label('Invoice all services')
                    ->icon(Heroicon::OutlinedDocumentPlus)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalDescription('Create ONE consolidated invoice covering every active service that is not already on an open invoice. It is not emailed automatically — review it on the Invoices tab, then use "Email to client".')
                    ->action(function () {
                        /** @var User $client */
                        $client = $this->getOwnerRecord();

                        $invoice = app(RecurringBillingService::class)->invoiceAllServicesFor($client);

                        if ($invoice === null) {
                            Notification::make()
                                ->title('Nothing to invoice')
                                ->body('Every active service is already covered by an open invoice.')
                                ->warning()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title("Invoice {$invoice->reference} created — ৳".number_format((float) $invoice->total, 2))
                            ->body('Review it on the Invoices tab, then use "Email to client" to send it.')
                            ->success()
                            ->send();
                    }),
                CreateAction::make()->label('New service'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
