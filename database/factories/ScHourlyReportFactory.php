<?php

namespace Database\Factories;

use App\Holidays;
use App\Models\ScAccount;
use App\Models\ScMonthlyReport;
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

    public function onPeakForPeriod(ScMonthlyReport $report): Factory
    {
        $hour = $report->period_start_at->addDay();
        if (
            Holidays::independenceDay($hour->year)->isSameDay($hour)
            || Holidays::laborDay($hour->year)->isSameDay($hour)
            || $hour->isWeekend()
        ) {
            $hour = $hour->next(Carbon::MONDAY);
        }

        $hour = $hour->setHour(16);

        return $this->state(fn () => [ 'hour_at' => $hour ]);
    }

    public function offPeakForPeriod(ScMonthlyReport $report): Factory
    {
        $hour = $report->period_start_at->addDay();

        return $this->state(fn () => [ 'hour_at' => $hour->setHour(0) ]);
    }
}
