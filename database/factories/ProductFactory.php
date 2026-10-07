<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->sentence(3),
            'slug' => fake()->unique()->slug(3),
            'category' => fake()->randomElement(['Eletrônicos', 'Livros', 'Casa']),
            'condition' => 'Muito bem conservado',
            'description' => fake()->paragraph(),
            'pix_price' => fake()->randomFloat(2, 20, 3000),
            'marketplace_price' => fake()->randomFloat(2, 30, 3500),
            'status' => 'available',
        ];
    }
}
