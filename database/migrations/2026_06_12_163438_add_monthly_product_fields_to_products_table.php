<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_monthly_product')->default(false)->after('is_controlled');
            $table->unsignedInteger('monthly_order')->nullable()->after('is_monthly_product');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_monthly_product', 'monthly_order']);
        });
    }
};