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
            'sku' => strtoupper(fake()->unique()->bothify('SKU-########')),
            'slug' => fake()->unique()->slug(),
            'title' => ['en' => ucfirst(fake()->words(3, true))],
            'description' => ['en' => fake()->paragraph()],
            'is_active' => true,
            'category_id' => null,
            'price' => fake()->randomFloat(2, 1, 1000),
            'stock_quantity' => fake()->numberBetween(0, 500),
            'image' => fake()->imageUrl(),
            'meta_title' => ['en' => fake()->sentence()],
            'meta_description' => ['en' => fake()->sentence()],
            'meta_keywords' => implode(', ', fake()->words(5)),
            'og_image' => fake()->imageUrl(),
        ];
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
