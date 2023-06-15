<?php

use App\Filament\Widgets\DailyReportsBackfillWidget;
use App\Filament\Widgets\DailyReportsTableWidget;
use App\Models\User;
use function Pest\Laravel\{actingAs, get};

test('unauthenticated users cannot access daily reports page', function () {
    get('/daily-reports')
        ->assertRedirect('/login');
});

test('authenticated users can access daily reports page', function () {
    actingAs(User::factory()->create())
        ->get('/daily-reports')
        ->assertOk();
});

test('the daily reports list is present', function () {
    actingAs(User::factory()->create())
        ->get('/daily-reports')
        ->assertSeeLivewire(DailyReportsTableWidget::class);
});

test('the daily reports backfill widget is present', function () {
    actingAs(User::factory()->create())
        ->get('/daily-reports')
        ->assertSeeLivewire(DailyReportsBackfillWidget::class);
});
