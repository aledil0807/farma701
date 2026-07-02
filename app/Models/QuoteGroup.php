<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteGroup extends Model
{
    protected $fillable = [
        'quote_id',
        'name',
        'position',
        'subtotal_usd',
        'subtotal_bs',
    ];

    protected $casts = [
        'subtotal_usd' => 'decimal:2',
        'subtotal_bs' => 'decimal:2',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function items()
    {
        return $this->hasMany(QuoteItem::class)->orderBy('position');
    }
}