<?php

use App\Livewire\DailyReportsTable;
use App\Jobs\UpdateDailyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScDailyReport;
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

test('its okay if no daily reports exist', function () {
    livewire(DailyReportsTable::class)
        ->assertOk();
});

test('it lists daily reports for the current user', function () {
    $myReports = ScDailyReport::factory(10)->for($this->account)->create();
    $notMyReports = ScDailyReport::factory(10)
        ->for(ScAccount::factory()->create())
        ->create();

    livewire(DailyReportsTable::class)
        ->assertCanSeeTableRecords($myReports)
        ->assertCanNotSeeTableRecords($notMyReports);
});

test('it batches daily report job', function () {
    freezeSecond();
    Bus::fake();
    ScAccount::factory()->for($this->user)->create();

    livewire(DailyReportsTable::class)->call('updateDailyReports');

    Bus::assertBatched(function (PendingBatch $batch) {
        return $batch->jobs->count() === 2
            && $batch->jobs->reduce(function (?bool $valid, UpdateDailyReportsJob $job) {
                if ($valid === false) {
                    return $valid;
                }

                return $job->startDate->equalTo(now()->subMonth())
                    && $job->endDate->equalTo(now())
                    && $this->user->scAccounts->pluck('id')->contains($job->account->getKey());
            });
    });
});
