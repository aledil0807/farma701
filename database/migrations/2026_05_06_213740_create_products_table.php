<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id()->unique();;
            $table->string('name');

            // Relaciones (Foreign Keys)
            $table->foreignId('category_id')->constrained();
            $table->foreignId('laboratory_id')->constrained();

            $table->boolean('has_iva')->default(false); // Aquí guardaremos true/false
            $table->decimal('price', 10, 2);
            $table->boolean('is_controlled')->default(false);
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
