<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'link',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getImageUrlAttribute(): string
    {
        if ($this->image_path) {
            return asset('storage/banners/' . $this->image_path);
        }

        return asset('img/no-image.png');
    }
}
