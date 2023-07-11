<?php

use App\Http\Livewire\HourlyReportsTable;
use App\Jobs\UpdateHourlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScHourlyReport;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeSecond;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $credentials = ScCredentials::factory()->create();
    $this->account = ScAccount::factory()->for($credentials->user)->create();
    $this->user = $this->account->user;

    actingAs($this->user);
});

test('its okay if no hourly reports exist', function () {
    livewire(HourlyReportsTable::class)
        ->assertOk();
});

test('it lists hourly reports for the current user', function () {
    $myReports = ScHourlyReport::factory(10)->for($this->account)->create();
    $notMyReports = ScHourlyReport::factory(10)
        ->for(ScAccount::factory()->create())
        ->create();

    livewire(HourlyReportsTable::class)
        ->assertCanSeeTableRecords($myReports)
        ->assertCanNotSeeTableRecords($notMyReports);
});

test('it batches hourly report job', function () {
    freezeSecond();
    Bus::fake();
    ScAccount::factory()->for($this->user)->create();

    livewire(HourlyReportsTable::class)->call('updateHourlyReports');

    Bus::assertBatched(function (PendingBatch $batch) {
        return $batch->jobs->count() === 2
            && $batch->jobs->reduce(function (?bool $valid, UpdateHourlyReportsJob $job) {
                if ($valid === false) {
                    return $valid;
                }

                return $job->startDate->equalTo(now()->subDays(3))
                    && $job->endDate->equalTo(now()->subDays(2))
                    && $this->user->scAccounts->pluck('id')->contains($job->account->getKey());
            });
    });
});
