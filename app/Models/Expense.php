<?php

namespace App\Models;

use App\Enums\ExpenseCategory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    protected $fillable = [
        'expensed_at', 'category', 'description', 'vendor', 'amount',
        'method', 'receipt_path', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'expensed_at' => 'date',
            'category' => ExpenseCategory::class,
            'amount' => 'decimal:2',
        ];
    }
}
