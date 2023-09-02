<?php

use App\Livewire\ScCredentialsForm;
use App\Models\ScCredentials;
use App\Models\User;
use App\ScClient;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it prefills credentials', function () {
    $credentials = ScCredentials::factory()->create();

    actingAs($credentials->user);

    $component = livewire(ScCredentialsForm::class, [
        'credentials' => $credentials
    ]);

    expect($component->get('username'))->toBe($credentials->username);
    expect($component->get('password'))->toBe($credentials->password);
});

test('it saves new credentials', function () {
    $user = User::factory()->create();
    app()->bind(ScClient::class, function () {
        return Mockery::mock(ScClient::class, function ($mock) {
            $mock->shouldReceive('getJwt')->andReturn('abc123');
        });
    });

    actingAs($user);

    $component = livewire(ScCredentialsForm::class);

    $component->set('username', 'hello');
    $component->set('password', 'def456');
    $component->call('updateCredentials');

    $credentials = ScCredentials::first();
    expect($credentials->username)->toBe('hello');
    expect($credentials->jwt)->toBe('abc123');
    expect($credentials->password)->toBe('def456');
});

test('it updates existing credentials', function () {
    $credentials = ScCredentials::factory()->create();
    expect($credentials->username)->not->toBe('hello');
    expect($credentials->jwt)->not->toBe('abc123');
    expect($credentials->password)->not->toBe('def456');

    $user = $credentials->user;

    app()->bind(ScClient::class, function () {
        return Mockery::mock(ScClient::class, function ($mock) {
            $mock->shouldReceive('getJwt')->andReturn('abc123');
        });
    });

    actingAs($user);

    $component = livewire(ScCredentialsForm::class);

    $component->set('username', 'hello');
    $component->set('password', 'def456');
    $component->call('updateCredentials');

    $credentials->refresh();

    expect($credentials->username)->toBe('hello');
    expect($credentials->jwt)->toBe('abc123');
    expect($credentials->password)->toBe('def456');
});
