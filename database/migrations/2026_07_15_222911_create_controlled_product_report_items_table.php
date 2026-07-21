<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_product_report_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('controlled_product_report_id')
                ->constrained('controlled_product_reports')
                ->cascadeOnDelete();

            $table->string('product_name');
            $table->string('drugstore')->nullable();
            $table->string('invoice_number')->nullable();

            $table->unsignedInteger('pills_received')->default(0);
            $table->unsignedInteger('previous_stock')->default(0);
            $table->unsignedInteger('entries')->default(0);
            $table->unsignedInteger('exits')->default(0);
            $table->integer('current_stock')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_product_report_items');
    }
};