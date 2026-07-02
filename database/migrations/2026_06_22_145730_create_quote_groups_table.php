<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_groups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_id')
                ->constrained('quotes')
                ->cascadeOnDelete();

            $table->string('name')->default('Conjunto');
            $table->unsignedInteger('position')->default(1);

            $table->decimal('subtotal_usd', 12, 2)->default(0);
            $table->decimal('subtotal_bs', 14, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_groups');
    }
};