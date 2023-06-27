<?php

use App\Http\Livewire\DailyReportsBackfillForm;
use App\Models\ScAccount;
use App\Models\ScCredentials;
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
