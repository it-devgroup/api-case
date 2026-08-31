<?php

namespace Database\Factories;

use App\Models\ProductCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductCategory>
 */
class ProductCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(),
            'title' => ucfirst(fake()->words(3, true)),
            'description' => ['en' => fake()->paragraph()],
            'image' => fake()->imageUrl(),
            'meta_title' => ['en' => fake()->sentence()],
            'meta_description' => ['en' => fake()->sentence()],
            'meta_keywords' => implode(', ', fake()->words(5)),
            'og_image' => fake()->imageUrl(),
        ];
    }
}
