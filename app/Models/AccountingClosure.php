<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingClosure extends Model
{
    protected $fillable = [
        'cashier_id',
        'closure_date',
        'employee_name',
        'shift',
        'status',
        'notes',
    ];

    protected $casts = [
        'closure_date' => 'date',
    ];

    public function paymentTotals(): HasMany
    {
        return $this->hasMany(AccountingClosurePaymentTotal::class);
    }

    public function getTotalFacturadoBsAttribute(): float
    {
        return (float) $this->paymentTotals->sum('facturado_bs');
    }

    public function getTotalEntregadoBsAttribute(): float
    {
        return (float) $this->paymentTotals->sum('entregado_bs');
    }

    public function getTotalDiferenciaBsAttribute(): float
    {
        return $this->total_entregado_bs - $this->total_facturado_bs;
    }

    public function getTotalTransactionsAttribute(): int
    {
        return (int) $this->paymentTotals->sum('transactions_count');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(Cashier::class);
    }

    public function getCashierDisplayNameAttribute(): string
    {
        return $this->cashier?->name ?: $this->employee_name ?: '—';
    }
}