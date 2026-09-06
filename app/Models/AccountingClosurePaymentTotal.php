<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingClosurePaymentTotal extends Model
{
    protected $fillable = [
        'accounting_closure_id',
        'accounting_payment_method_id',
        'facturado_bs',
        'entregado_bs',
        'transactions_count',
    ];

    protected $casts = [
        'facturado_bs' => 'decimal:2',
        'entregado_bs' => 'decimal:2',
        'transactions_count' => 'integer',
    ];

    public function closure(): BelongsTo
    {
        return $this->belongsTo(AccountingClosure::class, 'accounting_closure_id');
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(AccountingPaymentMethod::class, 'accounting_payment_method_id');
    }

    public function getDiferenciaBsAttribute(): float
    {
        return (float) $this->entregado_bs - (float) $this->facturado_bs;
    }
}