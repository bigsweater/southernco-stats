<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScCredentials>
 */
class ScCredentialsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'jwt' => $this->faker->randomAscii(),
            'user_id' => User::factory(),
        ];
    }
}
