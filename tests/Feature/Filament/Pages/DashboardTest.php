<?php

use App\Filament\Widgets\CurrentStatsWidget;
use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $this->get('/')->assertRedirect('/login');
});

test('authenticated users can access the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertOk();
});

test('it shows current usage widgets', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertSeeLivewire(CurrentStatsWidget::class);
});
