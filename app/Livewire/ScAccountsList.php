<?php

namespace App\Livewire;

use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\ScClient;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Livewire\Component;

class ScAccountsList extends Component implements HasTable, HasForms
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected $listeners = [
        'scJwtStored' => 'updateAccounts'
    ];

    public function render()
    {
        return view('livewire.sc-accounts-list');
    }

    public function getCredentialsProperty(): ?ScCredentials
    {
        return auth()->user()->scCredentials;
    }

    public function updateAccounts(): void
    {
        $client = new ScClient($this->credentials);

        $accounts = $client->getAccounts();

        foreach ($accounts as $data) {
            $account = ScAccount::fromApiResponse($data);
            $account->user()->associate(auth()->user());
            extract($client->getServicePointNumber($account));
            $account->meter_number = $meterNumber;
            $account->service_point_number = $servicePointNumber;

            ScAccount::updateOrCreate(
                ['account_number' => $account->account_number, 'user_id' => $account->user_id],
                $account->attributesToArray()
            );
        }
    }

    protected function table(Table $table): Table
    {
        return $table
            ->query(auth()->user()->scAccounts()->getQuery())
            ->columns([
                IconColumn::make('is_primary')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->label('Primary')
                    ->trueColor('success'),
                TextColumn::make('account_number')->label('Account Number'),
                TextColumn::make('description')->label('Description'),
        ]);
    }
}
