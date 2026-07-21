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

        $normalizedSearch = self::normalizeSearchText($search);

        $terms = collect(explode(' ', $normalizedSearch))
            ->map(fn($term) => self::normalizeSearchText($term))
            ->filter()
            ->values();

        if ($terms->isEmpty()) {
            return $query;
        }

        $wrappedColumn = self::wrappedSearchTextSql();
        $termsCount = $terms->count();

        $query->where(function ($mainQuery) use ($terms, $aliases, $wrappedColumn, $termsCount) {
            foreach ($terms as $term) {
                $expandedTerms = $aliases[$term] ?? [$term];

                $expandedTerms = collect($expandedTerms)
                    ->map(fn($expandedTerm) => self::normalizeSearchText((string) $expandedTerm))
                    ->filter()
                    ->unique()
                    ->values();

                $mainQuery->where(function ($termQuery) use ($expandedTerms, $wrappedColumn, $termsCount) {
                    foreach ($expandedTerms as $expandedTerm) {
                        if (mb_strlen($expandedTerm) === 1) {
                            // 1. Letra como palabra exacta: " c "
                            $termQuery->orWhereRaw(
                                "{$wrappedColumn} LIKE ?",
                                ['% ' . $expandedTerm . ' %']
                            );

                            // 2. Letra seguida de número: k1, k2, b12, etc.
                            foreach (range(0, 9) as $digit) {
                                $termQuery->orWhereRaw(
                                    "{$wrappedColumn} LIKE ?",
                                    ['% ' . $expandedTerm . $digit . '%']
                                );
                            }

                            // 3. Si la búsqueda tiene más de una palabra,
                            // permite que la letra sea el inicio de una palabra:
                            // "centella a" encuentra "centella asiatica"
                            if ($termsCount > 1) {
                                $termQuery->orWhereRaw(
                                    "{$wrappedColumn} LIKE ?",
                                    ['% ' . $expandedTerm . '%']
                                );
                            }
                        } else {
                            $termQuery->orWhere('search_text', 'like', '%' . $expandedTerm . '%');
                        }
                    }
                });
            }
        });

        $searchTerms = collect(explode(' ', $normalizedSearch))
            ->filter()
            ->values();

        $orderSql = "
        CASE
            WHEN {$wrappedColumn} LIKE ? THEN 0
    ";

        $orderBindings = [
            '% ' . $normalizedSearch . ' %',
        ];

        if ($searchTerms->count() >= 2) {
            $lastTerm = $searchTerms->last();

            if (mb_strlen($lastTerm) === 1) {
                $basePhrase = $searchTerms->implode(' ');

                foreach (range(0, 9) as $digit) {
                    $orderSql .= " WHEN {$wrappedColumn} LIKE ? THEN 1 ";
                    $orderBindings[] = '% ' . $basePhrase . $digit . '%';
                }

                // Ejemplo:
                // "centella a" prioriza "centella asiatica"
                $orderSql .= " WHEN {$wrappedColumn} LIKE ? THEN 2 ";
                $orderBindings[] = '% ' . $basePhrase . '%';
            }
        }

        $orderSql .= "
            WHEN search_text LIKE ? THEN 3
            ELSE 4
        END
    ";

        $orderBindings[] = $normalizedSearch . '%';

        return $query->orderByRaw($orderSql, $orderBindings);
    }

    private static function wrappedSearchTextSql(): string
    {
        $driver = config('database.default');

        if ($driver === 'sqlite') {
            return "' ' || search_text || ' '";
        }

        return "CONCAT(' ', search_text, ' ')";
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
                (string) $laboratoryName
            )
        );
    }
}
