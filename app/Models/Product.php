<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'sku', 
        'name', 
        'description', 
        'has_iva', 
        'price', 
        'is_controlled', 
        'image_path',
        'category_id',   // Nuevo campo
        'laboratory_id'  // Nuevo campo
    ];

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
