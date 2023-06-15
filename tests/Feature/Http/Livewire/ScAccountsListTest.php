<?php

use App\Http\Livewire\ScAccountsList;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

test('it gets credentials', function () {
    $credentials = ScCredentials::factory()->create();

    actingAs($credentials->user);

    expect(
        livewire(ScAccountsList::class)->get('credentials')->is($credentials)
    )->toBeTrue();
});


test('it displays accounts', function () {
    $account = ScAccount::factory()->create();
    actingAs($account->user);

    livewire(ScAccountsList::class)->assertCanSeeTableRecords(ScAccount::all());
});

test('it does not display other people\'s accounts', function () {
    $me = ScAccount::factory()->create()->user;
    $someoneElse = ScAccount::factory()->create()->user;

    actingAs($me);

    livewire(ScAccountsList::class)
        ->assertCanSeeTableRecords($me->scAccounts)
        ->assertCanNotSeeTableRecords($someoneElse->scAccounts);
});

test('it updates accounts', function () {
    $credentials = ScCredentials::factory()->create();
    actingAs($credentials->user);

    Http::fakeSequence()
        ->push([
            'Data' => [[
                'AccountNumber' => '123',
                'AccountType' => '0',
                'Company' => '2',
                'PrimaryAccount' => 'Y',
                'Description' => 'hey hi hello',
            ]]
        ])
        ->push([
            'Data' => [
                'meterAndServicePoints' => [[
                    'meterNumber' => '456',
                    'servicePointNumber' => '789'
                ]]
            ]
        ]);

    livewire(ScAccountsList::class)->call('updateAccounts');

    expect(ScAccount::first()->attributesToArray())->toMatchArray([
        'account_number' => 123,
        'account_type' => 0,
        'company' => 2,
        'is_primary' => true,
        'description' => 'hey hi hello',
        'meter_number' => 456,
        'service_point_number' => 789,
    ]);
});
