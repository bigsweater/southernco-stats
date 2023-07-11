<?php

use App\Http\Livewire\DailyReportsBackfillForm;
use App\Jobs\UpdateDailyReportsJob;
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

    $credentials = ScCredentials::factory()->create();
    $this->account = ScAccount::factory()->for($credentials->user)->create();
    $this->user = $credentials->user;

    actingAs($this->user);
});

test('it sets reasonable defaults', function () {
    $component = livewire(DailyReportsBackfillForm::class);

    expect($component->get('accountId'))->toBe($this->account->getKey());
    expect($component->get('from'))->toBe(now()->subMonth()->toDateTimeString());
});

test('from must be present', function () {
    livewire(DailyReportsBackfillForm::class)
        ->fillForm([
            'from' => ''
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from' => 'required']);
});

test('from must be at least a month ago', function () {
    livewire(DailyReportsBackfillForm::class)
        ->fillForm([
            'from' => now()->toDateTimeString()
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from']);

    livewire(DailyReportsBackfillForm::class)
        ->fillForm([
            'from' => now()->subMonth()->toDateTimeString()
        ])
        ->call('backfill')
        ->assertHasNoFormErrors(['from']);
});

test('account must be present', function () {
    livewire(DailyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => null
        ])
        ->call('backfill')
        ->assertHasFormErrors(['accountId' => 'required']);
});

test('it batches a job for one month of backfill', function () {
    livewire(DailyReportsBackfillForm::class)
        ->fillForm()
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        $job = $batch->jobs->first();
        return $job::class === UpdateDailyReportsJob::class
            && $job->startDate->equalTo(now()->subMonth())
            && $job->endDate->equalTo(now());
    });
});

test('it batches one job per month of backfill', function () {
    livewire(DailyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => $this->account->getKey(),
            'from' => now()->subYear()
        ])
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        return $batch->jobs->count() === 12
            && $batch->jobs->reduce(function (
                ?bool $result,
                UpdateDailyReportsJob $job,
                int $key
            ) use ($batch) {
                if ($result === false) {
                    return $result;
                }

                $monthIndex = $batch->jobs->count() - $key;

                return $job->startDate->equalTo(now()->subMonths($monthIndex))
                    && $job->endDate->equalTo(now()->subMonths($monthIndex - 1));
            });
    });
});
