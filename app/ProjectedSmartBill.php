<?php

namespace App;

use App\Models\ScHourlyReport;
use App\Models\ScMonthlyReport;
use App\Traits\HasMemory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ProjectedSmartBill
{
    use HasMemory;

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
    public static float $demandRateUsd = 8.68;
    public static float $onPeakRateUsd = 0.101909;
    public static float $offPeakRateUsd = 0.010895;


    public function __construct(
        public ScMonthlyReport $monthlyReport
    ) {
    }

    public function demandCost(): float|null
    {
        if (is_null($this->demand())) {
            return null;
        }

        return $this->demand() * $this::$demandRateUsd;
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
     * 14:00 and 19:00 June through September.
     *
     * Here we filter out weekends and holidays for the current billing
     * month, and select only hours which fall in the peak window.
     */
    private function getOnPeakData(): \stdClass
    {
        return DB::query()->fromSub(
            DB::table('sc_hourly_reports')
                ->selectRaw(<<<SQL
                extract(hour FROM hour_at) AS hour,
                hour_at,
                usage_kwh,
                cost_usd,
                peak_hours_from,
                peak_hours_to
                SQL)
                ->where('sc_account_id', $this->monthlyReport->sc_account_id)
                ->where('hour_at', '>=', $this->monthlyReport->period_start_at)
                ->where('hour_at', '<', $this->monthlyReport->period_end_at)
                ->whereRaw("date_trunc('day', hour_at) >= ?", [$this->smartRateStart()])
                ->whereRaw("date_trunc('day', hour_at) < ?", [$this->smartRateEnd()])
                ->whereRaw('extract(dow FROM hour_at) != 0') // Sunday
                ->whereRaw('extract(dow FROM hour_at) != 6') // Saturday
                ->whereRaw("date_trunc('day', hour_at) != ?", [Holidays::laborDay($this->monthlyReport->period_start_at->year)])
                ->whereRaw("date_trunc('day', hour_at) != ?", [Holidays::independenceDay($this->monthlyReport->period_start_at->year)]),
            'hours'
        )
            ->selectRaw('sum(hours.usage_kwh) as peak_hours_usage_kwh, sum(hours.cost_usd) as peak_hours_cost_usd')
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
        $year = $this->monthlyReport->period_start_at->year;
        $laborDay = Holidays::laborDay($year);
        $independenceDay = Holidays::independenceDay($year);
        $smartRateStart = $this->smartRateStart();
        $smartRateEnd = $this->smartRateEnd();

        return DB::query()->fromSub(
            DB::table('sc_hourly_reports')
                ->selectRaw(<<<SQL
                extract(hour FROM hour_at) AS hour,
                hour_at,
                usage_kwh,
                cost_usd,
                CASE
                    WHEN date_trunc('day', hour_at) < '$smartRateStart' THEN 0
                    WHEN date_trunc('day', hour_at) >= '$smartRateEnd' THEN 0
                    WHEN extract(dow FROM hour_at) = 0 THEN 0
                    WHEN extract(dow FROM hour_at) = 6 THEN 0
                    WHEN date_trunc('day', hour_at) = '$laborDay' THEN 0
                    WHEN date_trunc('day', hour_at) = '$independenceDay' THEN 0
                    WHEN peak_hours_to IS NULL THEN 0
                ELSE peak_hours_to
                END AS off_peak_start,
                CASE
                    WHEN date_trunc('day', hour_at) < '$smartRateStart' THEN 24
                    WHEN date_trunc('day', hour_at) >= '$smartRateEnd' THEN 24
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
            ->selectRaw('sum(hours.usage_kwh) as off_peak_hours_usage_kwh, sum(hours.cost_usd) as off_peak_hours_cost_usd')
            ->whereRaw('hours.hour >= hours.off_peak_start')
            ->whereRaw('hours.hour < hours.off_peak_end')
            ->first();
    }

    private function smartRateStart(): Carbon
    {
        return Carbon::create($this->monthlyReport->period_start_at->year, 6, 1, 0, 0, 0);
    }

    private function smartRateEnd(): Carbon
    {
        return Carbon::create($this->monthlyReport->period_start_at->year, 10, 1, 0, 0, 0);
    }

    public function totalCost(): float|int|null
    {
        return $this->onPeakCost() + $this->offPeakCost();
    }

    public function totalUsage(): float|int|null
    {
        return $this->onPeakUsage() + $this->offPeakUsage();
    }

    /**
     * Get the sum of cost and usage from hourly reports for the current
     * monthlyReport.
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
}
