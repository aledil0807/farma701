<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'id',
        'name',
        'description',
        'has_iva',
        'price',
        'cantidad',
        'is_controlled',
        'image_path',
        'category_id',   // Nuevo campo
        'laboratory_id',  // Nuevo campo
        'is_monthly_product',
        'monthly_order',
    ];

    // Dentro de la clase Product
    public function getImageUrlAttribute()
    {
        if ($this->image_path) {
            // Esto generará automáticamente la URL correcta: http://localhost:8000/storage/products/imagen.jpg
            return asset('storage/products/' . $this->image_path);
        }

        // Imagen por defecto si no hay nada en la base de datos
        return asset('img/no-image.png');
    }
    // Relación: Un producto pertenece a una categoría
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Relación: Un producto pertenece a un laboratorio
    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }
}
