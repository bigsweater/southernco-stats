<?php

use App\Jobs\UpdateDailyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScDailyReport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\freezeSecond;

beforeEach(function () {
    freezeSecond();

    $credentials = ScCredentials::factory()->create();
    $this->account = ScAccount::factory()->for($credentials->user)->create();
});

test('it stores values in correct position', function (array $response) {
    Http::fake([
        '*' => Http::response($response)
    ]);

    (new UpdateDailyReportsJob($this->account))->handle();
    $firstReport = ScDailyReport::find(1);
    $secondReport = ScDailyReport::find(2);

    expect($firstReport->scAccount->is($this->account))->toBeTrue();
    expect($secondReport->scAccount->is($this->account))->toBeTrue();

    expect($firstReport->weekday_cost_usd)->toBe(123.01);
    expect($firstReport->weekend_cost_usd)->toBeNull();
    expect($firstReport->weekday_usage_kwh)->toBe(22.2);
    expect($firstReport->weekend_usage_kwh)->toBeNull();
    expect($firstReport->temp_high_f)->toBe(55.0);
    expect($firstReport->temp_low_f)->toBe(12.0);
    expect($firstReport->alert_cost)->toBe(18.0);
    expect($firstReport->overage_low_kwh)->toBe(8.0);
    expect($firstReport->overage_high_kwh)->toBe(18.0);
    expect($firstReport->day_at->equalTo(now()->subDay()))->toBeTrue();

    expect($secondReport->weekday_cost_usd)->toBeNull();
    expect($secondReport->weekend_cost_usd)->toBe(123.01);
    expect($secondReport->weekday_usage_kwh)->toBeNull();
    expect($secondReport->weekend_usage_kwh)->toBe(22.2);
    expect($secondReport->temp_high_f)->toBe(98.6);
    expect($secondReport->temp_low_f)->toBe(22.0);
    expect($secondReport->alert_cost)->toBe(18.0);
    expect($secondReport->overage_low_kwh)->toBe(10.0);
    expect($secondReport->overage_high_kwh)->toBe(22.0);
    expect($secondReport->day_at->equalTo(now()))->toBeTrue();
})->with([
    fn () => ['Data' => [
        'Data' => json_encode([
            'xAxis' => [
                'labels' => [
                    now()->subDay()->toString(),
                    now()->toString(),
                ]
            ],
            'series' => [
                'weekdayCost' => ['data'  => [['x' => 0, 'y' => '123.01']]],
                'weekdayUsage' => ['data'  => [['x' => 0, 'y' => '22.2']]],
                'weekendCost' => ['data'  => [['x' => 1, 'y' => '123.01']]],
                'weekendUsage' => ['data'  => [['x' => 1, 'y' => '22.2']]],
                'highTemp' => ['data'  => [['x' => 0, 'y' => '55'], ['x' => 1, 'y' => '98.6']]],
                'lowTemp' => ['data'  => [['x' => 0, 'y' => '12'], ['x' => 1, 'y' => '22.0']]],
                'alertCost' => ['data'  => [['x' => 0, 'y' => '18'], ['x' => 1, 'y' => '18']]],
                'overage' => ['data'  => [
                    ['x' => 0, 'low' => '8', 'high' => '18'],
                    ['x' => 1, 'low' => '10', 'high' => '22'],
                ]],
                'avgDailyCost' => ['data'  => [['x' => 1, 'y' => '123.01'], ['x' => 1, 'y' => '123.01']]],
            ]
        ])
    ]]
]);

test('it does not crash if data is empty', function () {
    Http::fake([
        '*' => Http::response(['Data' => [
            'Data' => ''
        ]])
    ]);

    expect((new UpdateDailyReportsJob($this->account))->handle())
        ->not->toThrow(\Throwable::class);
    expect(ScDailyReport::all())->toBeEmpty();
});
