<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeRate extends Model
{
    protected $fillable = [
        'rate',
        'source',
        'is_active',
        'effective_date',
    ];
}