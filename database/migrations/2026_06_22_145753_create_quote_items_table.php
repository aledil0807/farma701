<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('quote_group_id')
                ->constrained('quote_groups')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->nullable()
                ->constrained('products')
                ->nullOnDelete();

            $table->string('product_name');
            $table->string('laboratory_name')->nullable();

            $table->unsignedInteger('quantity')->default(1);

            $table->decimal('unit_price_usd', 12, 2)->default(0);
            $table->decimal('unit_price_bs', 14, 2)->default(0);

            $table->decimal('subtotal_usd', 12, 2)->default(0);
            $table->decimal('subtotal_bs', 14, 2)->default(0);

            $table->unsignedInteger('position')->default(1);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};