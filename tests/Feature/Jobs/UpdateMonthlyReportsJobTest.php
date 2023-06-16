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

test('it stores monthly reports', function () {
    freezeSecond();

    Http::fake([
        '*' => Http::response(['Data' => [
            'Data' => json_encode([
                'xAxis' => [
                    'dates' => [
                        [
                            'startDate' => now()->toString(),
                            'endDate' => now()->addMonth()->toString(),
                        ]
                    ]
                ],
                'series' => [
                    'cost' => [ 'data'  => [ [ 'y' => '123.01' ] ] ],
                    'usage' => [ 'data'  => [ [ 'y' => '22.2' ] ] ],
                    'highTemp' => [ 'data'  => [ [ 'y' => '98.6' ] ] ],
                    'lowTemp' => [ 'data'  => [ [ 'y' => '22.0' ] ] ],
                ]
            ])
        ]])
    ]);

    (new UpdateMonthlyReportsJob($this->account))->handle();
    $report = ScMonthlyReport::first();

    expect($report->scAccount->is($this->account))->toBeTrue();
    expect($report->cost_usd)->toBe(123.01);
    expect($report->usage_kwh)->toBe(22.2);
    expect($report->temp_high_f)->toBe(98.6);
    expect($report->temp_low_f)->toBe(22.0);
    expect($report->period_start_at->equalTo(now()))->toBeTrue();
    expect($report->period_end_at->equalTo(now()->addMonth()))->toBeTrue();
});

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
