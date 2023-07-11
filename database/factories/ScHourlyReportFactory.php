<?php

namespace Database\Factories;

use App\Models\ScAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScHourlyReport>
 */
class ScHourlyReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'sc_account_id' => ScAccount::factory(),
            'hour_at' => $this->faker->dateTime(),
            'cost_usd' => $this->faker->randomFloat(2, 0.01, 999.00),
            'usage_kwh' => $this->faker->randomFloat(2, 0.01, 999.00),
            'temp_f' => $this->faker->randomFloat(2, 0.01, 999.00),
            'peak_hours_from' => 14,
            'peak_hours_to' => 19,
        ];
    }
}
