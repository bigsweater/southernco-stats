<?php

namespace Database\Factories;

use App\Holidays;
use App\Models\ScAccount;
use App\Models\ScMonthlyReport;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

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

    public function onPeakForPeriod(
        CarbonImmutable $start,
    ): Factory {
        $hour = $start->next(Carbon::TUESDAY);

        if (Holidays::independenceDay($hour->year)->isSameDay($hour)) {
            $hour = $start->next(Carbon::WEDNESDAY);
        }

        return $this->state(function ($attributes) use ($hour): array {
            return [
                'hour_at' => $hour->setHour(
                    $attributes['peak_hours_from'] ?? 17
                ),
            ];
        });
    }

    public function offPeakForPeriod(CarbonImmutable $start): Factory {
        $hour = $start->addDay()->setHour(0);

        return $this->state(fn () => [
            'hour_at' => $hour,
        ]);
    }
}
