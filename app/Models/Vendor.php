<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'opening_balance', 'notes'];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * How much of the pre-system debt is still unpaid: the opening balance
     * minus every "Previous due payment" expense recorded for this vendor.
     */
    public function previousBalanceRemaining(): float
    {
        $paid = (float) $this->expenses()
            ->where('category', ExpenseCategory::PreviousDue)
            ->sum('amount');

        return max(0, round((float) $this->opening_balance - $paid, 2));
    }
}
