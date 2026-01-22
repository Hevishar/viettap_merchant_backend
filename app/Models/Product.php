<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'image',
        'category',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public static function factory()
    {
        return \Database\Factories\ProductFactory::new();
    }
}
