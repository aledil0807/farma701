<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Laboratory;
use Maatwebsite\Excel\Concerns\ToModel;

class ProductsImport implements ToModel
{
    public function model(array $row)
    {
        $productId = trim((string) ($row['0'] ?? ''));

        if ($productId === '') {
            return null;
        }

        $categoryName = trim((string) ($row['2'] ?? 'SIN CATEGORÍA'));
        $laboratoryName = trim((string) ($row['3'] ?? 'SIN LABORATORIO'));

        $category = Category::firstOrCreate([
            'name' => $categoryName !== '' ? $categoryName : 'SIN CATEGORÍA',
        ]);

        $laboratory = Laboratory::firstOrCreate([
            'name' => $laboratoryName !== '' ? $laboratoryName : 'SIN LABORATORIO',
        ]);

        Product::updateOrCreate(
            [
                'id' => $productId,
            ],
            [
                'name' => $row['1'] ?? '',
                'category_id' => $category->id,
                'laboratory_id' => $laboratory->id,
                'cantidad' => $row['5'] ?? 0,
                'has_iva' => (($row['4'] ?? null) == '1'),
                'price' => $row['7'] ?? 0,
                'is_controlled' => (($row['8'] ?? null) === 'S'),
                'image_path' => $row['6'] ?? null,
                'is_active' => true,
            ]
        );

        return null;
    }
}