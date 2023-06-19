<?php

use App\Http\Livewire\MonthlyReportsTable;
use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScMonthlyReport;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it lists monthly reports', function () {
    $reports = ScMonthlyReport::factory(10)->for(ScAccount::factory())->create();
    actingAs($reports->first()->scAccount->user);

    livewire(MonthlyReportsTable::class)
        ->assertCanSeeTableRecords($reports);
});

test('it does not list others\' monthly reports', function () {
    $reports = ScMonthlyReport::factory(10)->for(ScAccount::factory())->create();
    $somebodyElsesReports = ScMonthlyReport::factory(10)->for(ScAccount::factory())->create();
    actingAs($reports->first()->scAccount->user);

    livewire(MonthlyReportsTable::class)
        ->assertCanSeeTableRecords($reports)
        ->assertCanNotSeeTableRecords($somebodyElsesReports);
});

test('it dispatches a monthly reports update job', function () {
    Bus::fake();
    $account = ScAccount::factory()->create();
    ScCredentials::factory()->for($account->user)->create();

    actingAs($account->user);

    livewire(MonthlyReportsTable::class)->call('updateMonthlyReports');

    Bus::assertBatched(function (PendingBatch $batch) use ($account) {
        $job = $batch->jobs->first();

        return $job::class === UpdateMonthlyReportsJob::class
            && $job->account->is($account);
    });
});
