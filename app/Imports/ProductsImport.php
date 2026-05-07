<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Laboratory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // 1. Normalización de Categoría
        // firstOrCreate busca por el nombre, si no existe, lo crea y nos devuelve el objeto
        $category = Category::firstOrCreate(['name' => $row['f']]);

        // 2. Normalización de Laboratorio
        $laboratory = Laboratory::firstOrCreate(['name' => $row['d']]);

        // 3. Creación del Producto
        return new Product([
            'id'           => $row['A'], // Usamos la columna A del Excel como identificador único
            'name'          => $row['b'], // Usamos la columna B del Excel para el nombre del producto
            'category_id'   => $category->id,
            'laboratory_id' => $laboratory->id,
            'has_iva'       => ($row['iva'] === 'S'), // Convierte 'S' en true, cualquier otra cosa en false
            'price'         => $row['h'], // Usamos la columna H del Excel para el precio
            'is_controlled' => ($row['i'] === 'S'), // Usamos la columna I del Excel para determinar si es controlado (S/N)
            'image_path'    => $row['g'], // Usamos la columna G del Excel para la ruta de la imagen
        ]);
    }
}
