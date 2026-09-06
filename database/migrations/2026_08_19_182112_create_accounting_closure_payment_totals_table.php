<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_closure_payment_totals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('accounting_closure_id')
                ->constrained('accounting_closures')
                ->cascadeOnDelete();

            $table->foreignId('accounting_payment_method_id')
                ->constrained('accounting_payment_methods')
                ->cascadeOnDelete();

            $table->decimal('facturado_bs', 14, 2)->default(0);
            $table->decimal('entregado_bs', 14, 2)->default(0);

            $table->unsignedInteger('transactions_count')->default(0);

            $table->timestamps();

            $table->unique([
                'accounting_closure_id',
                'accounting_payment_method_id',
            ], 'closure_payment_method_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_closure_payment_totals');
    }
};