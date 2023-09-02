<?php

use App\Livewire\MonthlyReportsBackfillForm;
use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\freezeSecond;
use function Pest\Livewire\livewire;

beforeEach(function () {
    freezeSecond();
    Bus::fake();

    $credential = ScCredentials::factory()->create();
    $this->account = ScAccount::factory()->for($credential->user)->create();
    $this->user = $credential->user;

    actingAs($this->user);
});

test('it sets reasonable defaults', function () {
    $component = livewire(MonthlyReportsBackfillForm::class);

    expect($component->get('accountId'))->toBe($this->account->getKey());
    expect($component->get('from'))->toBe(now()->subYear()->toDateTimeString());
});

test('from must be present', function () {
    livewire(MonthlyReportsBackfillForm::class)
        ->fillForm([
            'from' => ''
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from' => 'required']);
});

test('from must be at least a year ago', function () {
    livewire(MonthlyReportsBackfillForm::class)
        ->fillForm([
            'from' => now()->toDateTimeString()
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from']);
});

test('account must be present', function () {
    livewire(MonthlyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => null
        ])
        ->call('backfill')
        ->assertHasFormErrors(['accountId' => 'required']);
});

test('it batches a job for one year of backfill', function () {
    livewire(MonthlyReportsBackfillForm::class)
        ->fillForm()
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        $job = $batch->jobs->first();
        return $job::class === UpdateMonthlyReportsJob::class
            && $job->startDate->equalTo(now()->subYear())
            && $job->endDate->equalTo(now());
    });
});

test('it batches one job per year of backfill', function () {
    livewire(MonthlyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => $this->account->getKey(),
            'from' => now()->subYears(3)
        ])
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        return $batch->jobs->count() === 3
            && $batch->jobs->reduce(function (?bool $result, UpdateMonthlyReportsJob $job, int $key) use ($batch) {
                if ($result === false) {
                    return $result;
                }

                $yearIndex = $batch->jobs->count() - $key;

                return $job->startDate->equalTo(now()->subYears($yearIndex))
                    && $job->endDate->equalTo(now()->subYears($yearIndex - 1));
            });
    });
});
