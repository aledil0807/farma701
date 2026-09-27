<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('web_orders', function (Blueprint $table) {
            $table->id();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();

            $table->string('delivery_type')->nullable();
            $table->text('delivery_address')->nullable();

            $table->string('payment_method')->nullable();

            $table->unsignedInteger('items_count')->default(0);
            $table->unsignedInteger('units_count')->default(0);

            $table->decimal('subtotal_usd', 14, 2)->default(0);
            $table->decimal('subtotal_bs', 14, 2)->default(0);

            $table->decimal('discount_percent', 8, 2)->default(0);
            $table->decimal('discount_usd', 14, 2)->default(0);
            $table->decimal('discount_bs', 14, 2)->default(0);

            $table->decimal('total_usd', 14, 2)->default(0);
            $table->decimal('total_bs', 14, 2)->default(0);

            $table->string('status')->default('sent_to_whatsapp');

            $table->timestamp('ordered_at')->nullable();

            $table->timestamps();

            $table->index('ordered_at');
            $table->index('status');
            $table->index('payment_method');
            $table->index('delivery_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_orders');
    }
};