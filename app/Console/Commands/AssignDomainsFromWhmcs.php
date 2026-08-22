<?php

namespace App\Console\Commands;

use App\Models\Domain;
use App\Models\User;
use App\Services\WhmcsClient;
use Illuminate\Console\Command;

class AssignDomainsFromWhmcs extends Command
{
    protected $signature = 'domains:assign-from-whmcs {--commit : Actually assign; without this flag only a preview is shown}';

    protected $description = 'Assign panel domains to customers using the client records of the legacy WHMCS';

    public function handle(WhmcsClient $whmcs): int
    {
        $this->info('Fetching clients and domains from WHMCS…');

        $clients = collect($whmcs->allClients())->keyBy('id');

        // WHMCS domain names can carry notes like "example.com (inc. service charge)".
        $whmcsDomains = collect($whmcs->allDomains())
            ->mapWithKeys(function (array $domain) {
                $name = strtolower(strtok(trim((string) $domain['domainname']), ' '));

                return [$name => $domain];
            });

        $commit = (bool) $this->option('commit');
        $rows = [];
        $assigned = 0;
        $unmatched = 0;

        Domain::query()->whereNull('user_id')->orderBy('name')->each(
            function (Domain $domain) use ($clients, $whmcsDomains, $commit, &$rows, &$assigned, &$unmatched) {
                $match = $whmcsDomains->get($domain->name);
                $client = $match ? $clients->get((int) $match['userid']) : null;
                $email = strtolower(trim((string) data_get($client, 'email', '')));
                $name = trim(data_get($client, 'firstname', '').' '.data_get($client, 'lastname', ''))
                    ?: trim((string) data_get($client, 'companyname', ''));

                if ($client === null || $email === '') {
                    $rows[] = [$domain->name, '—', '—', 'no WHMCS match'];
                    $unmatched++;

                    return;
                }

                if ($commit) {
                    $user = User::firstOrCreate(
                        ['email' => $email],
                        ['name' => $name !== '' ? $name : $email, 'password' => str()->password(32)],
                    );

                    $domain->update(['user_id' => $user->id]);
                }

                $rows[] = [$domain->name, $name, $email, $commit ? 'ASSIGNED' : 'will assign'];
                $assigned++;
            }
        );

        $this->table(['Domain', 'Client', 'Email', 'Result'], $rows);

        $this->info(($commit ? 'Assigned' : 'Would assign')." {$assigned} domains; {$unmatched} without a WHMCS match; domains already assigned were left untouched.");

        if (! $commit) {
            $this->comment('Preview only — run again with --commit to apply.');
        }

        return self::SUCCESS;
    }
}
