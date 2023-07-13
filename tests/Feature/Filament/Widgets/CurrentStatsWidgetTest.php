<?php

use App\CurrentUsageCacheKey;
use App\Filament\Widgets\CurrentStatsWidget;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScMonthlyReport;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\freezeSecond;
use function Pest\Laravel\travelTo;
use function Pest\Livewire\livewire;

beforeEach(function () {
    Http::fake([
        '*' => Http::response($this->jsonResponse = <<<JSON
            {
            "StatusCode": 200,
            "Message": "Successfully retrieved My Power Usage data for Daily Graph",
            "MessageType": 0,
            "Data": {
                "ProjectedBillAmountHigh": 0.0,
                "ProjectedBillAmountLow": 0.0,
                "ProjectedUsageHigh": 0.0,
                "ProjectedUsageLow": 0.0,
                "AverageDailyCost": 2.15,
                "AverageDailyUsage": 38.0,
                "AverageDailyReceived": 0.0,
                "Days": 30.0,
                "DollarsToDate": 4.300232,
                "TotalkWhUsed": 76.0,
                "TotalkWhReceived": 0.0,
                "HasData": true,
                "HasEstimatedBill": false,
                "IsPartialMonth": true,
                "AlertThreshold": 19,
                "AlertThresholdExceeded": false,
                "IsSolarActive": false,
                "ProjectedReceivedHigh": 0.0,
                "ProjectedReceivedLow": 0.0
            },
            "ModelErrors": [],
            "IsScApiResult": true
        }
        JSON)
    ]);
    $this->credentials = ScCredentials::factory()->create();
    $this->user = $this->credentials->user;
});

test('it shows current usage widgets', function () {
    $credentials = ScCredentials::factory()->create();
    $user = $credentials->user;

    $this->actingAs($user);

    livewire(CurrentStatsWidget::class)
        ->assertDontSeeText('Missing credentials');
    Http::assertNothingSent();
});

test('it warns about missing credentials', function () {
    $this->actingAs(User::factory()->create());

    livewire(CurrentStatsWidget::class)
        ->assertSeeText('Missing credentials');
    Http::assertNothingSent();
});

test('it warns about missing accounts', function () {
    $credentials = ScCredentials::factory()->create();
    $this->actingAs($credentials->user);

    livewire(CurrentStatsWidget::class)
        ->assertSeeText('Missing account');
    Http::assertNothingSent();
});

test('it warns about missing billing periods', function () {
    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    $this->actingAs($credentials->user);

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->assertSeeText('Missing or outdated billing period');
    Http::assertNothingSent();
});

test('it warns about outdated billing periods', function () {
    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    ScMonthlyReport::factory()->create([
        'period_start_at' => now()->subMonths(3),
        'period_end_at' => now()->subMonths(2),
    ]);
    $this->actingAs($credentials->user);

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->assertSeeText('Missing or outdated billing period');
    Http::assertNothingSent();
});

test('it fetches stats', function () {
    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    ScMonthlyReport::factory()->for($account)->create([
        'period_start_at' => $start = now()->subDays(15),
        'period_end_at' => $end = now()->addDays(15),
    ]);
    $this->actingAs($credentials->user);

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->call('fetchStats');

    Http::assertSent(function (Request $request) use ($start, $end) {
        return $request->data()['StartDate'] === $start->format('m/d/Y')
            && $request->data()['EndDate'] === $end->format('m/d/Y');
    });
});

test('it caches fetched stats', function () {
    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    ScMonthlyReport::factory()->for($account)->create([
        'period_start_at' => now()->subDays(15),
        'period_end_at' => now()->addDays(15),
    ]);
    $this->actingAs($credentials->user);

    Cache::shouldReceive('remember')
        ->once()
        ->andReturn(json_decode($this->jsonResponse, true));

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->call('fetchStats');
});

test('it uses cached stats before cache expiry', function () {
    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    $report = ScMonthlyReport::factory()->for($account)->create([
        'period_start_at' => now()->subDays(15),
        'period_end_at' => now()->addDays(15),
    ]);
    Cache::remember(
        (string) new CurrentUsageCacheKey($report, $account->id),
        900,
        fn () => json_decode($this->jsonResponse, true)
    );
    $this->actingAs($credentials->user);

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->call('fetchStats');

    Http::assertNothingSent();
});

test('it makes request after cache expiry', function () {
    freezeSecond();

    $credentials = ScCredentials::factory()->create();
    $account = ScAccount::factory()->for($credentials->user)->create();
    $report = ScMonthlyReport::factory()->for($account)->create([
        'period_start_at' => $start = now()->subDays(15),
        'period_end_at' => $end = now()->addDays(15),
    ]);
    Cache::remember(
        (string) new CurrentUsageCacheKey($report, $account->id),
        900,
        fn () => json_decode($this->jsonResponse, true)
    );
    $this->actingAs($credentials->user);

    travelTo(now()->addSeconds(901));

    livewire(CurrentStatsWidget::class, ['scAccountId' => $account->getKey()])
        ->call('fetchStats');

    Http::assertSent(function (Request $request) use ($start, $end) {
        return $request->data()['StartDate'] === $start->format('m/d/Y')
            && $request->data()['EndDate'] === $end->format('m/d/Y');
    });
});
