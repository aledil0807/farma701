<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cashier extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function closures(): HasMany
    {
        return $this->hasMany(AccountingClosure::class);
    }
}