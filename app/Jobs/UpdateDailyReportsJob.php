<?php

namespace App\Jobs;

use App\Models\ScAccount;
use App\Models\ScDailyReport;
use App\ScClient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class UpdateDailyReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public function __construct(
        public ScAccount $account,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null,
    ) {
    }

    public function handle(): void
    {
        $client = new ScClient($this->account->user->scCredentials);
        $data = $client->getDaily($this->account, $this->startDate, $this->endDate);

        $dates = Arr::get($data, 'xAxis.labels');
        $weekdayCost = collect(Arr::get($data, 'series.weekdayCost.data', []));
        $weekdayUsage = collect(Arr::get($data, 'series.weekdayUsage.data', []));
        $weekendCost = collect(Arr::get($data, 'series.weekendCost.data', []));
        $weekendUsage = collect(Arr::get($data, 'series.weekendUsage.data', []));
        $highTemp = collect(Arr::get($data, 'series.highTemp.data', []));
        $lowTemp = collect(Arr::get($data, 'series.lowTemp.data', []));
        $alertCost = collect(Arr::get($data, 'series.alertCost.data', []));
        $overage = collect(Arr::get($data, 'series.overage.data', []));
        $averageDailyCost = collect(Arr::get($data, 'series.avgDailyCost.data', []));

        if (is_null($dates)) {
            throw new \RuntimeException('Missing dates from monthly response.');
        }

        foreach ($dates as $index => $date) {
            ScDailyReport::updateOrCreate([
                'sc_account_id' => $this->account->getKey(),
                'day_at' => new Carbon($date),
            ], [
                'weekday_cost_usd' => $this->getValueAtIndex($weekdayCost, $index),
                'weekday_usage_kwh' => $this->getValueAtIndex($weekdayUsage, $index),
                'weekend_cost_usd' => $this->getValueAtIndex($weekendCost, $index),
                'weekend_usage_kwh' => $this->getValueAtIndex($weekendUsage, $index),
                'temp_high_f' => $this->getValueAtIndex($highTemp, $index),
                'temp_low_f' => $this->getValueAtIndex($lowTemp, $index),
                'alert_cost' => $this->getValueAtIndex($alertCost, $index),
                'overage_low_kwh' => $this->getValueAtIndex($overage, $index, 'low'),
                'overage_high_kwh' => $this->getValueAtIndex($overage, $index, 'high'),
                'average_daily_cost_usd' => $this->getValueAtIndex($averageDailyCost, $index),
            ]);
        }
    }

    private function getValueAtIndex(Collection $data, int $index, ?string $key = 'y'): mixed
    {
        $item = $data->firstWhere('x', '=', $index);

        return $item[$key] ?? null;
    }
}
