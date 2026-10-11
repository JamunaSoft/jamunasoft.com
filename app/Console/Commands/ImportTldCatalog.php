<?php

namespace App\Console\Commands;

use App\Models\Tld;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportTldCatalog extends Command
{
    protected $signature = 'domains:import-tld-catalog';

    protected $description = 'Add all IANA root TLDs to the pricing list as inactive, without overwriting existing entries';

    public function handle(): int
    {
        $lines = file(resource_path('data/iana-root-tlds.txt'), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $rows = [];
        foreach ($lines as $line) {
            $suffix = strtolower(trim($line));
            if (! preg_match('/^[a-z0-9-]+$/', $suffix)) {
                continue;
            }
            $rows[] = [
                'tld' => $suffix, 'is_active' => false, 'auto_price_sync' => true,
                'created_at' => now(), 'updated_at' => now(),
            ];
        }
        $before = Tld::count();
        DB::transaction(function () use ($rows) {
            foreach (array_chunk($rows, 200) as $chunk) {
                DB::table('tlds')->insertOrIgnore($chunk);
            }
        });
        $this->info('Added '.(Tld::count() - $before).' TLDs. Unpriced entries stay inactive; existing entries were preserved.');

        return self::SUCCESS;
    }
}
