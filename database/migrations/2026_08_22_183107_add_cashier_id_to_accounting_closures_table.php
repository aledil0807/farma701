<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_closures', function (Blueprint $table) {
            $table->foreignId('cashier_id')
                ->nullable()
                ->after('id')
                ->constrained('cashiers')
                ->nullOnDelete();

            $table->index(['closure_date', 'cashier_id']);
        });
    }

    public function down(): void
    {
        Schema::table('accounting_closures', function (Blueprint $table) {
            $table->dropForeign(['cashier_id']);
            $table->dropIndex(['closure_date', 'cashier_id']);
            $table->dropColumn('cashier_id');
        });
    }
};