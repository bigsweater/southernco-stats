<?php

use App\Http\Livewire\HourlyReportsBackfillForm;
use App\Jobs\UpdateHourlyReportsJob;
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
    $component = livewire(HourlyReportsBackfillForm::class);

    expect($component->get('accountId'))->toBe($this->account->getKey());
    expect($component->get('from'))->toBe(now()->subDays(3)->toDateTimeString());
});

test('from must be present', function () {
    livewire(HourlyReportsBackfillForm::class)
        ->fillForm([
            'from' => ''
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from' => 'required']);
});

test('from must be at least three days ago', function () {
    livewire(HourlyReportsBackfillForm::class)
        ->fillForm([
            'from' => now()->toDateTimeString()
        ])
        ->call('backfill')
        ->assertHasFormErrors(['from']);

    livewire(HourlyReportsBackfillForm::class)
        ->fillForm([
            'from' => now()->subDays(3)->toDateTimeString()
        ])
        ->call('backfill')
        ->assertHasNoFormErrors(['from']);
});

test('account must be present', function () {
    livewire(HourlyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => null
        ])
        ->call('backfill')
        ->assertHasFormErrors(['accountId' => 'required']);
});

test('it batches a job for a day of backfill', function () {
    livewire(HourlyReportsBackfillForm::class)
        ->fillForm()
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        $job = $batch->jobs->first();
        return $job::class === UpdateHourlyReportsJob::class
            && $job->startDate->equalTo(now()->subDays(3))
            && $job->endDate->equalTo(now()->subDays(2));
    });
});

test('it batches one job per day of backfill', function () {
    livewire(HourlyReportsBackfillForm::class)
        ->fillForm([
            'accountId' => $this->account->getKey(),
            'from' => now()->subWeek()
        ])
        ->call('backfill');

    Bus::assertBatched(function (PendingBatch $batch) {
        return $batch->jobs->count() === 7
            && $batch->jobs->reduce(function (
                ?bool $result,
                UpdateHourlyReportsJob $job,
                int $key
            ) use ($batch) {
                if ($result === false) {
                    return $result;
                }

                $dayIndex = $batch->jobs->count() - $key;

                return $job->startDate->equalTo(now()->subDays($dayIndex))
                    && $job->endDate->equalTo(now()->subDays($dayIndex - 1));
            });
    });
});
