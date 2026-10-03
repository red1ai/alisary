<?php

namespace Database\Factories;

use App\Models\CareerApplication;
use App\Models\CareerOpening;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CareerApplication>
 */
class CareerApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference_number' => 'CA-'.Str::upper(Str::random(10)),
            'career_opening_id' => CareerOpening::factory(),
            'full_name' => fake()->name(),
            'phone' => fake()->numerify('9#######'),
            'email' => fake()->unique()->safeEmail(),
            'answers' => [],
            'files' => [],
        ];
    }
}
