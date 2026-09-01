<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;

/**
 * Read-only row of the ledger_entries SQL view: every payment received
 * ("in") and every expense ("out") in one money-in/money-out ledger.
 */
class LedgerEntry extends Model
{
    protected $table = 'ledger_entries';

    public $incrementing = false;

    protected $keyType = 'string';

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
            'amount' => 'decimal:2',
            'signed_amount' => 'decimal:2',
            'category' => ExpenseCategory::class,
        ];
    }
}
