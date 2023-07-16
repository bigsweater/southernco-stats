<?php

namespace App;

use App\Models\ScHourlyReport;
use App\Models\ScMonthlyReport;
use Illuminate\Support\Facades\DB;

class ProjectedBill
{
    private static float $demandCost = 8.68;

    private array $cache = [];

    public function __construct(
        public ScMonthlyReport $monthlyReport
    ) {
    }

    public function demandCost(): float|null
    {
        if (is_null($this->demand())) {
            return null;
        }

        return $this->demand() * $this::$demandCost;
    }

    public function demand(): float|int|null
    {
        // Demand is the highest single hour of usage in a given billing period.
        // https://www.georgiapower.com/content/dam/georgia-power/pdfs/residential-pdfs/tariffs/2023/TOU-RD-7.pdf
        return $this->remember(
            'currentDemand',
            fn () => ScHourlyReport::select('hour_at', 'usage_kwh')
                ->where('hour_at', '>=', $this->monthlyReport->period_start_at)
                ->where('hour_at', '<=', $this->monthlyReport->period_end_at)
                ->where('sc_account_id', $this->monthlyReport->sc_account_id)
                ->orderBy('usage_kwh', 'desc')
                ->limit(1)
                ->first()
                ?->usage_kwh
        );
    }

    public function onPeakCost(): float|int|null
    {
        return $this->remember(
            'on_peak',
            fn () => $this->getOnPeakData()
        )->peak_hours_cost_usd ?? null;
    }

    public function onPeakUsage(): float|int|null
    {
        return $this->remember(
            'on_peak',
            fn () => $this->getOnPeakData()
        )->peak_hours_usage_kwh ?? null;
    }

    private function getOnPeakData(): \stdClass
    {
        return DB::query()->fromSub(
            DB::table('sc_hourly_reports')
                ->select(DB::raw('extract(hour FROM hour_at) AS hour, *'))
                ->where('sc_account_id', $this->monthlyReport->sc_account_id)
                ->where('hour_at', '>=', $this->monthlyReport->period_start_at)
                ->where('hour_at', '<', $this->monthlyReport->period_end_at)
                ->where(DB::raw('extract(dow FROM hour_at)'), '!=', 0) // Sunday
                ->where(DB::raw('extract(dow FROM hour_at)'), '!=', 6) // Saturday
                ->where(DB::raw("date_trunc('day', hour_at)"), '!=', Holidays::laborDay($this->monthlyReport->period_start_at->year))
                ->where(DB::raw("date_trunc('day', hour_at)"), '!=', Holidays::independenceDay($this->monthlyReport->period_start_at->year)),
            'hours'
        )
            ->select(DB::raw('sum(hours.usage_kwh) as peak_hours_usage_kwh, sum(hours.cost_usd) as peak_hours_cost_usd'))
            ->whereRaw('hours.hour >= hours.peak_hours_from')
            ->whereRaw('hours.hour < hours.peak_hours_to')
            ->first();
    }

    public function offPeakCost(): float|int|null
    {
        return $this->remember(
            'off_peak',
            fn () => $this->getOffPeakData()
        )->off_peak_hours_cost_usd ?? null;
    }

    public function offPeakUsage(): float|int|null
    {
        return $this->remember(
            'off_peak',
            fn () => $this->getOffPeakData()
        )->off_peak_hours_usage_kwh ?? null;
    }

    public function getOffPeakData(): \stdClass
    {
        $laborDay = Holidays::laborDay($this->monthlyReport->period_start_at->year);
        $independenceDay = Holidays::independenceDay($this->monthlyReport->period_start_at->year);
        return DB::query()->fromSub(
            DB::table('sc_hourly_reports')
                ->selectRaw(<<<SQL
                extract(hour FROM hour_at) AS hour,
                *,
                CASE
                    WHEN extract(dow FROM hour_at) = 0 THEN 0
                    WHEN extract(dow FROM hour_at) = 6 THEN 0
                    WHEN date_trunc('day', hour_at) = '$laborDay' THEN 0
                    WHEN date_trunc('day', hour_at) = '$independenceDay' THEN 0
                    WHEN peak_hours_to IS NULL THEN 0
                ELSE peak_hours_to
                END AS off_peak_start,
                CASE
                    WHEN extract(dow FROM hour_at) = 0 THEN 24
                    WHEN extract(dow FROM hour_at) = 6 THEN 24
                    WHEN date_trunc('day', hour_at) = '$laborDay' THEN 24
                    WHEN date_trunc('day', hour_at) = '$independenceDay' THEN 24
                    WHEN peak_hours_from IS NULL THEN 24
                ELSE peak_hours_from
                END AS off_peak_end
                SQL)
                ->where('sc_account_id', $this->monthlyReport->sc_account_id)
                ->where('hour_at', '>=', $this->monthlyReport->period_start_at)
                ->where('hour_at', '<', $this->monthlyReport->period_end_at),
            'hours'
        )
            ->select(DB::raw('sum(hours.usage_kwh) as off_peak_hours_usage_kwh, sum(hours.cost_usd) as off_peak_hours_cost_usd'))
            ->whereRaw('hours.hour >= hours.off_peak_start')
            ->whereRaw('hours.hour < hours.off_peak_end')
            ->first();
    }

    public function totalConventionalCost(): float|int|null
    {
    }

    public function totalConventionalUsage(): float|int|null
    {
    }

    public function totalSmartCost(): float|int|null
    {
    }

    public function totalSmartUsage(): float|int|null
    {
    }

    private function remember(string $key, callable $callback): mixed
    {
        if ($this->cache[$key] ?? false) {
            return $this->cache['currentDemand'];
        }

        $this->cache[$key] = $callback();

        return $this->cache[$key];
    }
}
