<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Product;
use App\Models\Categories;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductsFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word,
            'sku' => $this->faker->unique()->word,
            'category_id' => Categories::factory(),
            'description' => $this->faker->paragraph,
            'image' => $this->faker->imageUrl(),
            'added_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}
