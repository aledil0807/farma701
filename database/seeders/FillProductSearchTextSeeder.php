<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class FillProductSearchTextSeeder extends Seeder
{
    public function run(): void
    {
        $updated = 0;

        Product::with(['laboratory'])->chunkById(200, function ($products) use (&$updated) {
            foreach ($products as $product) {
                $product->forceFill([
                    'search_text' => Product::makeSearchText(
                        $product->name,
                        $product->laboratory?->name
                    ),
                ])->save();

                $updated++;
            }
        });

        $this->command?->info("search_text actualizado en {$updated} productos.");
    }
}