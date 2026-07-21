<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ControlledProductReport extends Model
{
    protected $fillable = [
        'report_number',
        'title',
        'report_month',
        'category',
        'notes',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ControlledProductReportItem::class)
            ->orderBy('product_name');
    }
}