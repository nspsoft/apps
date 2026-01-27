<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
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
            'name' => $this->faker->words(3, true),
            'sku' => $this->faker->unique()->bothify('SKU-####'),
            'description' => $this->faker->sentence(),
            'type' => 'product',
            'product_type' => 'finished_good',
            'cost_price' => $this->faker->randomFloat(2, 1000, 50000),
            'selling_price' => $this->faker->randomFloat(2, 60000, 100000),
            'min_stock' => 10,
            'max_stock' => 100,
            'reorder_point' => 20,
            'is_active' => true,
        ];
    }
}
