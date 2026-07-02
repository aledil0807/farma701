<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_group_id',
        'product_id',
        'product_name',
        'laboratory_name',
        'quantity',
        'unit_price_usd',
        'unit_price_bs',
        'subtotal_usd',
        'subtotal_bs',
        'position',
    ];

    protected $casts = [
        'unit_price_usd' => 'decimal:2',
        'unit_price_bs' => 'decimal:2',
        'subtotal_usd' => 'decimal:2',
        'subtotal_bs' => 'decimal:2',
    ];

    public function group()
    {
        return $this->belongsTo(QuoteGroup::class, 'quote_group_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}