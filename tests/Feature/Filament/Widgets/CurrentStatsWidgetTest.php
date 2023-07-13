<?php

use App\Filament\Widgets\CurrentStatsWidget;
use App\Models\ScCredentials;
use App\Models\User;
use Illuminate\Support\Facades\Http;

use function Pest\Livewire\livewire;

beforeEach(function () {
    Http::fake();
    $this->credentials = ScCredentials::factory()->create();
    $this->user = $this->credentials->user;
});

test('it shows current usage widgets', function () {
    $credentials = ScCredentials::factory()->create();
    $user = $credentials->user;

    $this->actingAs($user);

    livewire(CurrentStatsWidget::class)
        ->assertDontSeeText('Missing credentials');
});

test('it warns about missing credentials', function () {
    $this->actingAs(User::factory()->create());

    livewire(CurrentStatsWidget::class)
        ->assertSeeText('Missing credentials');
});
