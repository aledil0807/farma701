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
        $category = Category::firstOrCreate(['name' => $row['C']]);

        // 2. Normalización de Laboratorio
        $laboratory = Laboratory::firstOrCreate(['name' => $row['D']]);

        // 3. Creación del Producto
        return new Product([
            'id'           => $row['A'], // Usamos la columna A del Excel como identificador único
            'name'          => $row['B'], // Usamos la columna B del Excel para el nombre del producto
            'category_id'   => $category->id,
            'laboratory_id' => $laboratory->id,
            'cantidad'      => $row['F'], // Usamos la columna F del Excel para la cantidad
            'has_iva'       => ($row['E'] === 'S'), // Convierte 'E' en true, cualquier otra cosa en false
            'price'         => $row['H'], // Usamos la columna H del Excel para el precio
            'is_controlled' => ($row['I'] === 'S'), // Usamos la columna I del Excel para determinar si es controlado (S/N)
            'image_path'    => $row['G'], // Usamos la columna G del Excel para la ruta de la imagen
        ]);
    }
}
