<?php

namespace App\Jobs;

use App\Models\ScAccount;
use App\Models\ScMonthlyReport;
use App\ScClient;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

class UpdateMonthlyReportsJob implements ShouldQueue
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
        $client = app(ScClient::class, [$this->account->user->scCredentials]);
        $data = $client->getMonthly($this->account, $this->startDate, $this->endDate);

        $dates = Arr::get($data, 'xAxis.dates', []);
        $cost = Arr::get($data, 'series.cost.data', []);
        $usage = Arr::get($data, 'series.usage.data', []);
        $highTemp = Arr::get($data, 'series.highTemp.data', []);
        $lowTemp = Arr::get($data, 'series.lowTemp.data', []);

        foreach ($dates as $index => $date) {
            ScMonthlyReport::updateOrCreate([
                'sc_account_id' => $this->account->getKey(),
                'period_start_at' => new Carbon($date['startDate']),
                'period_end_at' => new Carbon($date['endDate']),
            ], [
                'cost_usd' => $cost[$index]['y'] ?? null,
                'usage_kwh' => $usage[$index]['y'] ?? null,
                'temp_high_f' => $highTemp[$index]['y'] ?? null,
                'temp_low_f' => $lowTemp[$index]['y'] ?? null,
            ]);
        }
    }
}
