<?php

use App\Jobs\UpdateHourlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScHourlyReport;
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

    (new UpdateHourlyReportsJob($this->account))->handle();
    $firstReport = ScHourlyReport::find(1);
    $secondReport = ScHourlyReport::find(2);
    $thirdReport = ScHourlyReport::find(3);

    expect($firstReport->scAccount->is($this->account))->toBeTrue();
    expect($secondReport->scAccount->is($this->account))->toBeTrue();
    expect($thirdReport->scAccount->is($this->account))->toBeTrue();

    expect($firstReport->hour_at->is(now()->subHours(2)))->toBeTrue();
    expect($firstReport->cost_usd)->toBeNull();
    expect($firstReport->usage_kwh)->toBeNull();
    expect($firstReport->temp_f)->toBe(18.0);

    expect($secondReport->hour_at->is(now()->subHour()))->toBeTrue();
    expect($secondReport->cost_usd)->toBe(123.01);
    expect($secondReport->usage_kwh)->toBe(22.2);
    expect($secondReport->temp_f)->toBe(55.0);

    expect($thirdReport->hour_at->is(now()))->toBeTrue();
    expect($thirdReport->cost_usd)->toBe(11.0);
    expect($thirdReport->usage_kwh)->toBe(13.0);
    expect($thirdReport->temp_f)->toBe(98.6);
})->with([
    fn () => generateResponseForTime(now()->toImmutable())
]);

test('it does not crash if data is empty', function () {
    Http::fake([
        '*' => Http::response(['Data' => [
            'Data' => ''
        ]])
    ]);

    expect((new UpdateHourlyReportsJob($this->account))->handle())
        ->not->toThrow(\Throwable::class);
    expect(ScHourlyReport::all())->toBeEmpty();
});

function generateResponseForTime(CarbonImmutable $baseTime): array
{
    return ['Data' => [
        'Data' => json_encode([
            'xAxis' => [
                'labels' => [
                    $baseTime->subHours(2)->toString(),
                    $baseTime->subHour()->toString(),
                    $baseTime->toString(),
                ]
            ],
            'series' => [
                'cost' => ['data'  => [['x' => 1, 'y' => '123.01'], ['x' => 2, 'y' => '11']]],
                'usage' => ['data'  => [['x' => 1, 'y' => '22.2'], ['x' => 2, 'y' => '13']]],
                'temp' => ['data'  => [['x' => 0, 'y' => '18'], ['x' => 1, 'y' => '55'], ['x' => 2, 'y' => '98.6']]],
            ]
        ])
    ]];
}
