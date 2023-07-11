<?php

use App\Filament\Widgets\HourlyReportsBackfillWidget;
use App\Filament\Widgets\HourlyReportsTableWidget;
use App\Models\User;
use function Pest\Laravel\{actingAs, get};

test('unauthenticated users cannot access hourly reports page', function () {
    get('/hourly-reports')
        ->assertRedirect('/login');
});

test('authenticated users can access hourly reports page', function () {
    actingAs(User::factory()->create())
        ->get('/hourly-reports')
        ->assertOk();
});

test('the hourly reports list is present', function () {
    actingAs(User::factory()->create())
        ->get('/hourly-reports')
        ->assertSeeLivewire(HourlyReportsTableWidget::class);
});

test('the hourly reports backfill widget is present', function () {
    actingAs(User::factory()->create())
        ->get('/hourly-reports')
        ->assertSeeLivewire(HourlyReportsBackfillWidget::class);
});
