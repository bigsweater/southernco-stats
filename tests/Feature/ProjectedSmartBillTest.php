<?php

use App\Models\ScDailyReport;
use App\Models\ScHourlyReport;
use App\Models\ScMonthlyReport;
use App\ProjectedSmartBill;

beforeEach(function () {
    $this->report = ScMonthlyReport::factory()->create();
    $this->bill = new ProjectedSmartBill($this->report);
});

test('current demand is null if no hourly data exists for current period', function () {
    ScHourlyReport::factory()->create([
        'sc_account_id' => $this->report->sc_account_id,
        'hour_at' => $this->report->period_start_at->subDay()
    ]);

    expect($this->bill->demand())->toBeNull();
});

test('current demand matches the highest hour in the current period', function () {
    ScHourlyReport::factory()->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 5.0,
        'hour_at' => $this->report->period_start_at->addDay()
    ]);

    ScHourlyReport::factory()->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 4.0,
        'hour_at' => $this->report->period_start_at->addDays(2)
    ]);

    expect($this->bill->demand())->toBe(5.0);
});

test('it calculates cost of demand', function () {
    ScHourlyReport::factory()->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 5.0,
        'hour_at' => $this->report->period_start_at->addDay()
    ]);

    expect($this->bill->demandCost())->toBe(5.0 * 8.68);
});

test('cost is null if demand is null', function () {
    expect($this->bill->demandCost())->toBeNull();
});

test('it calculates on peak usage', function () {
    // Our usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 5.0,
    ]);
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 50.0,
    ]);

    // Somebody else's usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create();

    expect($this->bill->onPeakUsage())->toBe(10);
});

test('it calculates on peak cost', function () {
    // Our usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'cost_usd' => 5.0,
    ]);
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'cost_usd' => 50.0,
    ]);

    // Somebody else's usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create();

    expect($this->bill->onPeakCost())->toBe(10);
});

test('it calculates off peak usage', function () {
    // Our usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 5.0,
    ]);
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'usage_kwh' => 50.0,
    ]);

    // Somebody else's usage
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create();

    expect($this->bill->offPeakUsage())->toBe(100);
});

test('it calculates off peak cost', function () {
    // Our usage
    ScHourlyReport::factory(2)->onPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'cost_usd' => 5.0,
    ]);
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create([
        'sc_account_id' => $this->report->sc_account_id,
        'cost_usd' => 50.0,
    ]);

    // Somebody else's usage
    ScHourlyReport::factory(2)->offPeakForPeriod($this->report)->create();

    expect($this->bill->offPeakCost())->toBe(100);
});

test('it calculates total usage', function () {
    // Our usage
    ScDailyReport::factory(2)->forMonthlyReport($this->report)->create([
        'weekday_usage_kwh' => 5.0,
        'weekend_usage_kwh' => null,
    ]);
    ScDailyReport::factory(2)->forMonthlyReport($this->report)->create([
        'weekend_usage_kwh' => 2.0,
        'weekday_usage_kwh' => null,
    ]);

    // Somebody else's usage
    ScDailyReport::factory(2)->create();

    expect($this->bill->totalUsage())->toBe(14);
});

test('it calculates total cost', function () {
    // Our usage
    ScDailyReport::factory(2)->forMonthlyReport($this->report)->create([
        'weekday_cost_usd' => 5.0,
        'weekend_cost_usd' => null,
    ]);
    ScDailyReport::factory(2)->forMonthlyReport($this->report)->create([
        'weekend_cost_usd' => 2.0,
        'weekday_cost_usd' => null,
    ]);

    // Somebody else's usage
    ScDailyReport::factory(2)->create();

    expect($this->bill->totalCost())->toBe(14);
});
