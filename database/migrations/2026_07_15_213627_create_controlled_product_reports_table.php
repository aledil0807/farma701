<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('controlled_product_reports', function (Blueprint $table) {
            $table->id();

            $table->string('report_number')->nullable()->unique();
            $table->string('title')->nullable();
            $table->string('report_month', 7)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('controlled_product_reports');
    }
};