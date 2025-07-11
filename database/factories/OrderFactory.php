<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'status' => 'reserved',
            'type' => 'warehouse',
        ];
    }
}
