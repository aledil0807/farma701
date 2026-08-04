<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerProductRequest extends Model
{
    protected $fillable = [
        'customer_name',
        'customer_phone',
        'searched_product',
        'sent_at',
    ];
}