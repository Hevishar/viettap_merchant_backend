<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Product;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'description' => $this->faker->sentence,
            'price' => $this->faker->numberBetween(1000, 1000000),
            'image' => $this->faker->imageUrl(640, 480),
            'category' => $this->faker->word,
        ];
    }
}