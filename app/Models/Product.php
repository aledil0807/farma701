<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'is_active',
        'search_text',
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

    public function scopeSearchByTerms($query, ?string $search)
    {
        $search = trim(preg_replace('/\s+/', ' ', (string) $search));

        if ($search === '') {
            return $query;
        }

        $rawAliases = config('product_search.aliases', []);

        $aliases = collect($rawAliases)
            ->mapWithKeys(function ($values, $key) {
                return [
                    self::normalizeSearchText((string) $key) => collect($values)
                        ->map(fn($value) => self::normalizeSearchText((string) $value))
                        ->filter()
                        ->unique()
                        ->values()
                        ->all(),
                ];
            })
            ->all();

        $terms = collect(explode(' ', $search))
            ->map(fn($term) => self::normalizeSearchText($term))
            ->filter(fn($term) => mb_strlen($term) >= 2)
            ->values();

        if ($terms->isEmpty()) {
            return $query;
        }

        return $query->where(function ($mainQuery) use ($terms, $aliases) {
            foreach ($terms as $term) {
                $expandedTerms = $aliases[$term] ?? [$term];

                $expandedTerms = collect($expandedTerms)
                    ->map(fn($expandedTerm) => self::normalizeSearchText((string) $expandedTerm))
                    ->filter()
                    ->unique()
                    ->values();

                $mainQuery->where(function ($termQuery) use ($expandedTerms) {
                    foreach ($expandedTerms as $expandedTerm) {
                        $termQuery->orWhere('search_text', 'like', '%' . $expandedTerm . '%');
                    }
                });
            }
        });
    }

    public static function normalizeSearchText(?string $text): string
    {
        $text = (string) $text;

        $text = Str::ascii($text);
        $text = mb_strtolower($text);
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    public static function makeSearchText(
        ?string $productName,
        ?string $laboratoryName = null,
        ?string $categoryName = null
    ): string {
        return self::normalizeSearchText(
            trim(
                (string) $productName . ' ' .
                (string) $laboratoryName . ' ' .
                (string) $categoryName
            )
        );
    }
}
