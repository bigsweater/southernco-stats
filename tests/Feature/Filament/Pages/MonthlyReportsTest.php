<?php

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
