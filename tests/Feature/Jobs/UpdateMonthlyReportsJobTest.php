<?php

use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScMonthlyReport;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\freezeSecond;

beforeEach(function () {
    $credentials = ScCredentials::factory()->create();
    $this->account = ScAccount::factory()->for($credentials->user)->create();
});
test('it stores monthly reports', function (array $response) {
    freezeSecond();

    Http::fake([
        '*' => Http::response($response)
    ]);

    (new UpdateMonthlyReportsJob($this->account))->handle();
    $report = ScMonthlyReport::first();

    expect($report->scAccount->is($this->account))->toBeTrue();
    expect($report->cost_usd)->toBe(123.01);
    expect($report->usage_kwh)->toBeNull();
    expect($report->temp_high_f)->toBeNull();
    expect($report->temp_low_f)->toBeNull();
    expect($report->period_start_at->equalTo(now()->subMonths(2)))->toBeTrue();
    expect($report->period_end_at->equalTo(now()->subMonth()->subDay()))->toBeTrue();
})->with('response');

test('it stores values in correct position', function (array $response) {
    freezeSecond();

    Http::fake([
        '*' => Http::response($response)
    ]);

    (new UpdateMonthlyReportsJob($this->account))->handle();
    $secondReport = ScMonthlyReport::find(2);
    $thirdReport = ScMonthlyReport::find(3);

    expect($secondReport->scAccount->is($this->account))->toBeTrue();
    expect($secondReport->cost_usd)->toBeNull();
    expect($secondReport->usage_kwh)->toBe(22.2);
    expect($secondReport->temp_high_f)->toBeNull();
    expect($secondReport->temp_low_f)->toBeNull();
    expect($secondReport->period_start_at->equalTo(now()->subMonth()))->toBeTrue();
    expect($secondReport->period_end_at->equalTo(now()->subDay()))->toBeTrue();

    expect($thirdReport->scAccount->is($this->account))->toBeTrue();
    expect($thirdReport->cost_usd)->toBeNull();
    expect($thirdReport->usage_kwh)->toBeNull();
    expect($thirdReport->temp_high_f)->toBe(98.6);
    expect($thirdReport->temp_low_f)->toBe(22.0);
    expect($thirdReport->period_start_at->equalTo(now()))->toBeTrue();
    expect($thirdReport->period_end_at->equalTo(now()->addMonth()))->toBeTrue();
})->with('response');

test('it does not crash if data is empty', function () {
    freezeSecond();

    Http::fake([
        '*' => Http::response(['Data' => [
            'Data' => ''
        ]])
    ]);

    expect((new UpdateMonthlyReportsJob($this->account))->handle())
        ->not->toThrow(\Throwable::class);
    expect(ScMonthlyReport::all())->toBeEmpty();
});

dataset('response', [
    'response with unordered series' => [ ['Data' => [
        'Data' => json_encode([
            'xAxis' => [
                'dates' => [
                    [
                        'startDate' => now()->subMonths(2)->toString(),
                        'endDate' => now()->subMonth()->subDay()->toString(),
                    ],
                    [
                        'startDate' => now()->subMonth()->toString(),
                        'endDate' => now()->subDay()->toString(),
                    ],
                    [
                        'startDate' => now()->toString(),
                        'endDate' => now()->addMonth()->toString(),
                    ]
                ]
            ],
            'series' => [
                'cost' => ['data'  => [['x' => 0, 'y' => '123.01']]],
                'usage' => ['data'  => [['x' => 1, 'y' => '22.2']]],
                'highTemp' => ['data'  => [['x' => 2, 'y' => '98.6']]],
                'lowTemp' => ['data'  => [['x' => 2, 'y' => '22.0']]],
            ]
        ])
    ] ] ]
]);

