<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebOrderLaboratoryMetric extends Model
{
    protected $fillable = [
        'web_order_id',
        'laboratory_id',
        'laboratory_name',
        'products_count',
        'units_count',
        'total_usd',
        'total_bs',
    ];

    protected $casts = [
        'total_usd' => 'decimal:2',
        'total_bs' => 'decimal:2',
    ];

    public function webOrder(): BelongsTo
    {
        return $this->belongsTo(WebOrder::class);
    }

    public function laboratory(): BelongsTo
    {
        return $this->belongsTo(Laboratory::class);
    }
}