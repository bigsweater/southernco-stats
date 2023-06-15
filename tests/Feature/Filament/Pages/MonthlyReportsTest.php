<?php

use App\Filament\Widgets\MonthlyReportsBackfillWidget;
use App\Filament\Widgets\MonthlyReportsTableWidget;
use App\Models\User;
use function Pest\Laravel\{actingAs, get};

test('unauthenticated users cannot access monthly reports page', function () {
    get('/monthly-reports')
        ->assertRedirect('/login');
});

test('authenticated users can access monthly reports page', function () {
    actingAs(User::factory()->create())
        ->get('/monthly-reports')
        ->assertOk();
});

test('the monthly reports list is present', function () {
    actingAs(User::factory()->create())
        ->get('/monthly-reports')
        ->assertSeeLivewire(MonthlyReportsTableWidget::class);
});

test('the monthly reports backfill widget is present', function () {
    actingAs(User::factory()->create())
        ->get('/monthly-reports')
        ->assertSeeLivewire(MonthlyReportsBackfillWidget::class);
});
