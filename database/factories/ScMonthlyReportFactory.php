<?php

namespace Database\Factories;

use App\Models\ScAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScMonthlyReport>
 */
class ScMonthlyReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'period_start_at' => now()->subMonth(),
            'period_end_at' => now(),
            'cost_usd' => $this->faker->randomFloat(2, 0.01, 999.00),
            'usage_kwh' => $this->faker->randomFloat(2, 0.00, 999.00),
            'temp_high_f' => $this->faker->randomFloat(2, 0.00, 110.00),
            'temp_low_f' => $this->faker->randomFloat(2, 0.00, 110.00),
            'updated_at' => now(),
            'created_at' => now(),
            'sc_account_id' => ScAccount::factory(),
        ];
    }
}
