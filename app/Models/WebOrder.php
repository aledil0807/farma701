<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebOrder extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'delivery_type',
        'delivery_address',
        'payment_method',
        'items_count',
        'units_count',
        'subtotal_usd',
        'subtotal_bs',
        'discount_percent',
        'discount_usd',
        'discount_bs',
        'total_usd',
        'total_bs',
        'status',
        'ordered_at',
    ];

    protected $casts = [
        'subtotal_usd' => 'decimal:2',
        'subtotal_bs' => 'decimal:2',
        'discount_percent' => 'decimal:2',
        'discount_usd' => 'decimal:2',
        'discount_bs' => 'decimal:2',
        'total_usd' => 'decimal:2',
        'total_bs' => 'decimal:2',
        'ordered_at' => 'datetime',
    ];

    public function laboratoryMetrics(): HasMany
    {
        return $this->hasMany(WebOrderLaboratoryMetric::class);
    }
}