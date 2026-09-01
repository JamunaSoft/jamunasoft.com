<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\User;
use App\Services\WhmcsClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ImportWhmcsBilling extends Command
{
    protected $signature = 'whmcs:import-billing
        {--history : Also import Paid invoices (revenue history), not just Unpaid ones}
        {--commit : Actually write; without this flag only a preview is shown}';

    protected $description = 'Import client profiles (company, phone, address) and invoices from the legacy WHMCS';

    public function handle(WhmcsClient $whmcs): int
    {
        $commit = (bool) $this->option('commit');

        $this->info('Fetching WHMCS clients…');
        $clients = collect($whmcs->allClients());
        $localByEmail = User::whereDoesntHave('roles')->get()->keyBy(fn (User $user) => strtolower($user->email));

        // ── 1. Profile details ──────────────────────────────────────────
        $profileRows = [];

        foreach ($clients as $client) {
            $local = $localByEmail->get(strtolower(trim((string) $client['email'])));

            if ($local === null) {
                continue;
            }

            $details = $whmcs->clientDetails((int) $client['id']);

            $updates = array_filter([
                'company_name' => blank($local->company_name) ? trim((string) data_get($details, 'companyname')) : null,
                'phone' => blank($local->phone) ? trim((string) data_get($details, 'phonenumber')) : null,
                'address' => blank($local->address) ? trim((string) data_get($details, 'address1')) : null,
                'city' => blank($local->city) ? trim((string) data_get($details, 'city')) : null,
                'postal_code' => blank($local->postal_code) ? trim((string) data_get($details, 'postcode')) : null,
            ], fn ($value) => filled($value));

            if ($updates !== []) {
                $profileRows[] = [$local->email, implode(', ', array_keys($updates))];

                if ($commit) {
                    $local->update($updates);
                }
            }
        }

        $this->table(['Client', 'Fields '.($commit ? 'updated' : 'to update')], $profileRows);

        // ── 2. Invoices ─────────────────────────────────────────────────
        $statuses = $this->option('history') ? ['Unpaid', 'Paid'] : ['Unpaid'];
        $clientById = $clients->keyBy('id');
        $invoiceRows = [];
        $imported = 0;
        $skipped = 0;

        foreach ($statuses as $status) {
            $this->info("Fetching {$status} invoices…");

            foreach ($whmcs->invoicesByStatus($status) as $summary) {
                $whmcsId = (int) $summary['id'];
                $client = $clientById->get((int) $summary['userid']);
                $local = $client ? $localByEmail->get(strtolower(trim((string) $client['email']))) : null;

                if ($local === null) {
                    $invoiceRows[] = ["#{$whmcsId}", '—', $summary['total'], $status, 'no matching client'];
                    $skipped++;

                    continue;
                }

                if (Invoice::where('meta->whmcs_id', $whmcsId)->exists()) {
                    $invoiceRows[] = ["#{$whmcsId}", $local->email, $summary['total'], $status, 'already imported'];
                    $skipped++;

                    continue;
                }

                $invoiceRows[] = ["#{$whmcsId}", $local->email, $summary['total'], $status, $commit ? 'IMPORTED' : 'will import'];
                $imported++;

                if (! $commit) {
                    continue;
                }

                $detail = $whmcs->invoice($whmcsId);
                $total = (float) data_get($detail, 'total', 0);
                $balance = (float) data_get($detail, 'balance', $status === 'Paid' ? 0 : $total);
                $amountPaid = round($total - $balance, 2);
                $date = Carbon::parse((string) data_get($detail, 'date', now()->toDateString()));
                $datePaid = (string) data_get($detail, 'datepaid', '');

                $invoice = Invoice::create([
                    'reference' => sprintf('INV-WHMCS-%d', $whmcsId),
                    'user_id' => $local->id,
                    'status' => $status === 'Paid' ? InvoiceStatus::Paid : InvoiceStatus::Unpaid,
                    'subtotal' => (float) data_get($detail, 'subtotal', $total),
                    'discount' => max(0, (float) data_get($detail, 'credit', 0)),
                    'total' => $total,
                    'amount_paid' => $amountPaid,
                    'due_at' => Carbon::parse((string) data_get($detail, 'duedate', $date->toDateString())),
                    'paid_at' => $status === 'Paid' && $datePaid !== '' && ! str_starts_with($datePaid, '0000')
                        ? Carbon::parse($datePaid)
                        : null,
                    'notes' => 'Imported from WHMCS invoice #'.$whmcsId,
                    'meta' => ['whmcs_id' => $whmcsId],
                ]);

                // Keep chronology right for statements and previous-due math.
                $invoice->forceFill(['created_at' => $date])->save();

                foreach ((array) data_get($detail, 'items.item', []) as $item) {
                    $invoice->items()->create([
                        'description' => trim((string) data_get($item, 'description', 'Imported item')),
                        'quantity' => 1,
                        'unit_price' => (float) data_get($item, 'amount', 0),
                        'total' => (float) data_get($item, 'amount', 0),
                    ]);
                }

                if ($amountPaid > 0) {
                    $invoice->payments()->create([
                        'user_id' => $local->id,
                        'amount' => $amountPaid,
                        'method' => 'other',
                        'transaction_id' => 'WHMCS import #'.$whmcsId,
                        'paid_at' => $datePaid !== '' && ! str_starts_with($datePaid, '0000') ? Carbon::parse($datePaid) : $date,
                    ]);
                }
            }
        }

        $this->table(['WHMCS #', 'Client', 'Total', 'Status', 'Action'], $invoiceRows);
        $this->info("Invoices: {$imported} ".($commit ? 'imported' : 'to import').", {$skipped} skipped.");

        if (! $commit) {
            $this->warn('Preview only — run again with --commit to write.');
        }

        return self::SUCCESS;
    }
}
