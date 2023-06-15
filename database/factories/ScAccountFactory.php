<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScAccount>
 */
class ScAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'account_number' => $this->faker->randomNumber(),
            'service_point_number' => $this->faker->randomNumber(),
            'meter_number' => $this->faker->randomNumber(),
            'account_type' => 0,
            'company' => 2,
            'is_primary' => $this->faker->boolean(),
            'description' => $this->faker->streetAddress(),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }
}
