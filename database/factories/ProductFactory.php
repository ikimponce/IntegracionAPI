<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->word(),
            'marca' => $this->faker->company(),
            'codigo' => $this->faker->unique()->regexify('[A-Z]{5}[0-9]{3}'),
            'stock' => $this->faker->numberBetween(0, 100),
            'moneda' => $this->faker->randomElement(['CLP', 'USD', 'EUR']),
            'precio' => $this->faker->randomFloat(0, 1000, 100000),
        ];
    }
}
