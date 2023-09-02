<?php

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\CurrentStatsWidget;
use App\Models\User;

use function Pest\Livewire\livewire;

test('unauthenticated users are redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users can access the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

test('it shows current usage widgets', function () {
    $this->actingAs(User::factory()->create());
    livewire(Dashboard::class)->assertSeeLivewire(CurrentStatsWidget::class);
})->skip('Fix later');
