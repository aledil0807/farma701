<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();

            $table->string('quote_number')->nullable()->unique();

            $table->string('employee_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('draft');

            $table->decimal('exchange_rate', 12, 4)->default(1);
            $table->decimal('total_usd', 12, 2)->default(0);
            $table->decimal('total_bs', 14, 2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};