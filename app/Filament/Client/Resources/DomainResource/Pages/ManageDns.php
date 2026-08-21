<?php

namespace App\Filament\Client\Resources\DomainResource\Pages;

use App\Filament\Client\Resources\DomainResource;
use App\Services\Spaceship\DomainSyncService;
use App\Services\Spaceship\SpaceshipClient;
use App\Services\Spaceship\SpaceshipException;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ManageDns extends Page
{
    use InteractsWithRecord;

    protected static string $resource = DomainResource::class;

    protected string $view = 'filament.client.pages.manage-dns';

    protected static ?string $title = 'DNS Records';

    /** @var array<int, array<string, mixed>> */
    public array $records = [];

    public ?string $loadError = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->loadRecords();
    }

    public function getHeading(): string
    {
        return 'DNS — '.$this->record->name;
    }

    public function loadRecords(): void
    {
        // The in-panel DNS editor only talks to Spaceship for now.
        if ($this->record->registrar !== 'spaceship') {
            $this->records = [];
            $this->loadError = 'DNS for this domain is managed by our support team — open a support ticket and we will apply your changes right away.';

            return;
        }

        try {
            $this->records = app(SpaceshipClient::class)->listDnsRecords($this->record->name)['items'];
            $this->loadError = null;
        } catch (SpaceshipException $e) {
            $this->records = [];
            $this->loadError = 'DNS records could not be loaded right now — please try again in a moment.';
        }
    }

    public function removeRecord(int $index): void
    {
        $record = $this->records[$index] ?? null;

        if ($record === null) {
            return;
        }

        // The API deletes by matching the record object; "group" is display metadata.
        unset($record['group']);

        try {
            app(SpaceshipClient::class)->deleteDnsRecords($this->record->name, [$record]);
        } catch (SpaceshipException $e) {
            Notification::make()->title('Could not delete the record')->body($e->getMessage())->danger()->send();

            return;
        }

        Notification::make()->title('DNS record deleted')->success()->send();

        $this->loadRecords();
    }

    /**
     * The value of a record lives in a type-specific field; show the first
     * one present so unknown record types still render something useful.
     */
    public function recordValue(array $record): string
    {
        foreach (['address', 'cname', 'value', 'exchange', 'nameserver', 'target'] as $field) {
            if (isset($record[$field])) {
                return (string) $record[$field];
            }
        }

        $rest = array_diff_key($record, array_flip(['type', 'name', 'ttl', 'group']));

        return (string) json_encode($rest);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('addRecord')
                ->label('Add record')
                ->visible(fn () => $this->record->registrar === 'spaceship')
                ->icon(Heroicon::OutlinedPlus)
                ->schema([
                    Select::make('type')
                        ->options([
                            'A' => 'A — IPv4 address',
                            'AAAA' => 'AAAA — IPv6 address',
                            'CNAME' => 'CNAME — alias',
                            'TXT' => 'TXT — text',
                            'MX' => 'MX — mail server',
                        ])
                        ->live()
                        ->required(),
                    TextInput::make('name')
                        ->default('@')
                        ->required()
                        ->helperText('Use @ for the root domain, or a subdomain like www.'),
                    TextInput::make('value')
                        ->required()
                        ->label(fn (callable $get) => match ($get('type')) {
                            'A' => 'IPv4 address',
                            'AAAA' => 'IPv6 address',
                            'CNAME' => 'Target hostname',
                            'MX' => 'Mail server hostname',
                            default => 'Value',
                        }),
                    TextInput::make('priority')
                        ->numeric()
                        ->default(10)
                        ->visible(fn (callable $get) => $get('type') === 'MX'),
                    TextInput::make('ttl')
                        ->numeric()
                        ->default(3600)
                        ->required()
                        ->helperText('Time to live, in seconds.'),
                ])
                ->action(function (array $data) {
                    $item = [
                        'type' => $data['type'],
                        'name' => $data['name'],
                        'ttl' => (int) $data['ttl'],
                        ...match ($data['type']) {
                            'A', 'AAAA' => ['address' => $data['value']],
                            'CNAME' => ['cname' => $data['value']],
                            'MX' => ['exchange' => $data['value'], 'preference' => (int) ($data['priority'] ?? 10)],
                            default => ['value' => $data['value']],
                        },
                    ];

                    try {
                        app(SpaceshipClient::class)->saveDnsRecords($this->record->name, [$item]);
                    } catch (SpaceshipException $e) {
                        Notification::make()->title('Could not add the record')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    Notification::make()
                        ->title('DNS record added')
                        ->body('Changes can take up to a few hours to propagate worldwide.')
                        ->success()
                        ->send();

                    $this->loadRecords();
                }),
            Action::make('nameservers')
                ->label('Nameservers')
                ->visible(fn () => $this->record->registrar === 'spaceship')
                ->icon(Heroicon::OutlinedServerStack)
                ->color('gray')
                ->schema([
                    Textarea::make('hosts')
                        ->label('Nameservers (one per line)')
                        ->rows(4)
                        ->default(implode("\n", $this->record->nameservers ?? []))
                        ->required()
                        ->helperText('Careful: pointing the domain at other nameservers disables the DNS records managed here.'),
                ])
                ->action(function (array $data) {
                    $hosts = collect(explode("\n", (string) $data['hosts']))
                        ->map(fn (string $host) => strtolower(trim($host)))
                        ->filter()
                        ->values()
                        ->all();

                    if (count($hosts) < 2) {
                        Notification::make()->title('At least two nameservers are required')->danger()->send();

                        return;
                    }

                    try {
                        app(SpaceshipClient::class)->updateNameservers($this->record->name, $hosts);
                        app(DomainSyncService::class)->syncByName($this->record->name);
                    } catch (SpaceshipException $e) {
                        Notification::make()->title('Could not update nameservers')->body($e->getMessage())->danger()->send();

                        return;
                    }

                    $this->record->refresh();

                    Notification::make()->title('Nameservers updated')->success()->send();
                }),
        ];
    }
}
