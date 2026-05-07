<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    // Permitimos que el nombre se llene masivamente
    protected $fillable = ['name'];

    /**
     * Relación: Un laboratorio tiene muchos productos.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}