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
        'price' => 'decimal:2',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image);
    }

    public function getPriceVndAttribute()
    {
        return '₫' . number_format($this->price, 0, ',', '.');
    }

    public function getCategoryNameAttribute()
    {
        return $this->category;
    }

    public static function factory()
    {
        return \Database\Factories\ProductFactory::new();
    }
}
