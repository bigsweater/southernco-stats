<?php

namespace Database\Factories;

use App\Models\ScAccount;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ScDailyReport>
 */
class ScDailyReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $day = $this->faker->dateTime();
        $isWeekend = (new Carbon($day))->isWeekend();

        return [
            'sc_account_id' => ScAccount::factory(),
            'day_at' => now(),
            'weekday_cost_usd' => $isWeekend ? null : $this->faker->randomFloat(2, 0.01, 999.00),
            'weekday_usage_kwh' => $isWeekend ? null : $this->faker->randomFloat(2, 0.01, 999.00),
            'weekend_cost_usd' => $isWeekend ? $this->faker->randomFloat(2, 0.01, 999.00) : null,
            'weekend_usage_kwh' => $isWeekend ? $this->faker->randomFloat(2, 0.01, 999.00) : null,
            'temp_high_f' => $this->faker->randomFloat(2, 0.01, 999.00),
            'temp_low_f' => $this->faker->randomFloat(2, 0.01, 999.00),
            'alert_cost' => $this->faker->randomFloat(2, 0.01, 999.00),
            'overage_low_kwh' => $this->faker->randomFloat(2, 0.01, 999.00),
            'overage_high_kwh' => $this->faker->randomFloat(2, 0.01, 999.00),
            'average_daily_cost_usd' => $this->faker->randomFloat(2, 0.01, 999.00),
        ];
    }
}
