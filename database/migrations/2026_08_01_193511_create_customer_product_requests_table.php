<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_product_requests', function (Blueprint $table) {
            $table->id();

            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('searched_product');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_product_requests');
    }
};