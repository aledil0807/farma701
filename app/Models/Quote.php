<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Quote extends Model
{
    protected $fillable = [
        'quote_number',
        'employee_name',
        'customer_phone',
        'notes',
        'status',
        'exchange_rate',
        'total_usd',
        'total_bs',
        'created_by',
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'total_usd' => 'decimal:2',
        'total_bs' => 'decimal:2',
    ];

    public function groups()
    {
        return $this->hasMany(QuoteGroup::class)->orderBy('position');
    }
}