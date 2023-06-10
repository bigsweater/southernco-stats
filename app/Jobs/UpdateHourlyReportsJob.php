<?php

namespace App\Jobs;

use App\Models\ScAccount;
use App\Models\ScHourlyReport;
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

class UpdateHourlyReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public ScAccount $account,
        public ?Carbon $startDate = null,
        public ?Carbon $endDate = null
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->startDate) {
            $this->startDate = now()->subDays(3);
            $this->endDate = now()->subDays(2);
        }

        if ($this->startDate->greaterThanOrEqualTo(now()->subDays(2))) {
            logger('Cannot fetch hourly data after two days ago.', [
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
            ]);

            return;
        }

        $client = new ScClient($this->account->user->scCredentials);
        $data = $client->getHourly($this->account, $this->startDate, $this->endDate);

        $dates = Arr::get($data, 'xAxis.labels');
        $peakBoundaries = collect(Arr::get($data, 'timeOfUse'))->firstWhere('type', 0);
        $cost = collect(Arr::get($data, 'series.cost.data', []));
        $usage = collect(Arr::get($data, 'series.usage.data', []));
        $temp = collect(Arr::get($data, 'series.temp.data', []));

        foreach ($dates as $index => $date) {
            ScHourlyReport::updateOrCreate([
                'sc_account_id' => $this->account->getKey(),
                'hour_at' => new Carbon($date)
            ], [
                'cost_usd' => $this->getValueAtIndex($cost, $index),
                'usage_kwh' => $this->getValueAtIndex($usage, $index),
                'temp_f' => $this->getValueAtIndex($temp, $index),
                'peak_hours_from' => Arr::get($peakBoundaries, 'from'),
                'peak_hours_to' => Arr::get($peakBoundaries, 'to'),
            ]);
        }
    }

    private function getValueAtIndex(Collection $data, int $index, ?string $key = 'y'): mixed
    {
        $item = $data->firstWhere('x', '=', $index);

        return $item[$key] ?? null;
    }
}
