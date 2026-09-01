<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    protected $fillable = ['name', 'phone', 'email', 'notes'];

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
