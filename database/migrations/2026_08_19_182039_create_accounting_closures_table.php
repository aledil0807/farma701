<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_closures', function (Blueprint $table) {
            $table->id();

            $table->date('closure_date');
            $table->string('employee_name')->nullable();

            $table->string('shift')->nullable();
            $table->string('status')->default('open');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('closure_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_closures');
    }
};