<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Category;
use App\Models\Laboratory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToModel
{
    public function model(array $row)
    {
        // 1. Normalización de Categoría
        // firstOrCreate busca por el nombre, si no existe, lo crea y nos devuelve el objeto
        $category = Category::firstOrCreate(['name' => $row['2']]);

        // 2. Normalización de Laboratorio
        $laboratory = Laboratory::firstOrCreate(['name' => $row['3']]);

        // 3. Creación del Producto
        return new Product([
            'id'           => $row['0'], // Usamos la columna A del Excel como identificador único
            'name'          => $row['1'], // Usamos la columna B del Excel para el nombre del producto
            'category_id'   => $category->id,
            'laboratory_id' => $laboratory->id,
            'cantidad'      => $row['5'], // Usamos la columna F del Excel para la cantidad
            'has_iva'       => ($row['4'] == '1'), // Convierte 'E' en true, cualquier otra cosa en false
            'price'         => $row['7'], // Usamos la columna H del Excel para el precio
            'is_controlled' => ($row['8'] === 'S'), // Usamos la columna I del Excel para determinar si es controlado (S/N)
            'image_path'    =>  $row['6'], // Usamos la columna G del Excel para la ruta de la imagen
        ]);
    }
}
