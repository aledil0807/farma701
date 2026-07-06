<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Laboratory extends Model
{
    // Permitimos que el nombre se llene masivamente
    protected $fillable = ['name', 'logo_path',];


    /**
     * Relación: Un laboratorio tiene muchos productos.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
    public function getLogoUrlAttribute(): string
    {
        if ($this->logo_path) {
            return asset('storage/laboratories/' . $this->logo_path);
        }

        return asset('assets/img/labs/default-lab.png');
    }
    public function activeProducts()
    {
        return $this->hasMany(Product::class, 'laboratory_id')
            ->where('is_active', true)
            ->where('cantidad', '>', 0);
    }

    public function scopeVisible($query)
    {
        return $query->whereIn('id', function ($subQuery) {
            $subQuery->select('laboratory_id')
                ->from('products')
                ->where('is_active', 1)
                ->whereNotNull('laboratory_id');
        });
    }
}