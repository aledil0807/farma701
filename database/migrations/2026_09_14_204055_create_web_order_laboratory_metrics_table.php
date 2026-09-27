<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_order_laboratory_metrics', function (Blueprint $table) {
            $table->id();

            $table->foreignId('web_order_id')
                ->constrained('web_orders')
                ->cascadeOnDelete();

            $table->foreignId('laboratory_id')
                ->nullable()
                ->constrained('laboratories')
                ->nullOnDelete();

            $table->string('laboratory_name')->default('Sin laboratorio');

            $table->unsignedInteger('products_count')->default(0);
            $table->unsignedInteger('units_count')->default(0);

            $table->decimal('total_usd', 14, 2)->default(0);
            $table->decimal('total_bs', 14, 2)->default(0);

            $table->timestamps();

            $table->index('laboratory_id');
            $table->index('laboratory_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_order_laboratory_metrics');
    }
};