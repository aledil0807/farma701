<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ControlledProductReportItem extends Model
{
    protected $fillable = [
        'controlled_product_report_id',
        'product_name',
        'drugstore',
        'invoice_number',
        'units_per_box',
        'boxes_received',
        'pills_received',
        'previous_stock',
        'entries',
        'exits',
        'current_stock',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ControlledProductReport::class, 'controlled_product_report_id');
    }

    public static function calculateCurrentStock(int $previousStock, int $entries, int $exits): int
    {
        return $previousStock + $entries - $exits;
    }
}