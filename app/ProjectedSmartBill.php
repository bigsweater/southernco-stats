<?php

namespace App;

use App\Models\ScHourlyReport;
use App\Models\ScMonthlyReport;
use Illuminate\Support\Facades\DB;

class ProjectedSmartBill
{
    /**
     * These are dollars charged per kWH of usage or demand.
     * Because the Ga Power API is private and seems to be used exclusively
     * for presenting usage data in their dashboard, some days are missing from
     * the hourly reports (e.g. holidays).
     *
     * There are other tarrifs not accounted for here.
     *
     * @see https://www.georgiapower.com/content/dam/georgia-power/pdfs/residential-pdfs/tariffs/2023/TOU-RD-7.pdf
     */
    public static float $demandMultiple = 8.68;
    public static float $onPeakMultiple = 0.101909;
    public static float $offPeakMultiple = 0.010895;

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

        return $this->demand() * $this::$demandMultiple;
    }

    public function demand(): float|int|null
    {
        // Demand is the highest single hour of usage in a given billing period.
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

    /**
     * This query retrieves and sums all on-peak hours and dollars from the
     * hourly reports for the month stored in $monthlyReport.
     *
     * On-peak hours weekdays, except Independence and Labor days, between
     * 14:00 and 19:00 .
     *
     * Here we filter out weekends and holidays for the current billing
     * month, and select only hours which fall in the peak window.
     */
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

    /**
     * This query retrieves and sums all off-peak hours from the hourly_reports
     * table for the month stored in $monthlyReport.
     *
     * Off-peak hours are weekends, Independence and Labor days, and any
     * time before 4 or after 7 on weekdays.
     *
     * Here we set the off peak time to 0:00 - 24:00 on holidays or
     * weekends; otherwise we defer to the peak_hours_{from,to} columns.
     */
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

    public function totalCost(): float|int|null
    {
        return $this->remember(
            'days',
            fn () => $this->getDaysData()
        )->total_cost;
    }

    public function totalUsage(): float|int|null
    {
        return $this->remember(
            'days',
            fn () => $this->getDaysData()
        )->total_usage;
    }

    /**
     * Get the sum of cost and usage from daily reports for the current monthlyReport.
     *
     * We do this by the day (not the hour) since some hours are missing from
     * the database, and the month may be incomplete and therefore its totals
     * may be missing from teh API.
     *
     * We're assuming here that the daily cost takes into account off- and
     * on-peak rates.
     */
    private function getDaysData(): \stdClass
    {
        return DB::query()->fromSub(
            DB::table('sc_daily_reports')
                ->selectRaw(<<<SQL
                greatest(weekday_usage_kwh, weekend_usage_kwh) usage_kwh,
                greatest(weekday_cost_usd, weekend_cost_usd) cost_usd,
                *
                SQL)
                ->where('sc_account_id', $this->monthlyReport->sc_account_id)
                ->where('day_at', '>=', $this->monthlyReport->period_start_at)
                ->where('day_at', '<', $this->monthlyReport->period_end_at),
            'days'
        )
            ->select(DB::raw('sum(days.usage_kwh) as total_usage, sum(days.cost_usd) as total_cost'))
            ->first();
    }

    private function remember(string $key, callable $callback): mixed
    {
        if (boolval($this->cache[$key] ?? false)) {
            return $this->cache['currentDemand'];
        }

        $this->cache[$key] = $callback();

        return $this->cache[$key];
    }
}
