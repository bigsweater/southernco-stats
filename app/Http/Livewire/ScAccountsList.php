<?php

namespace App\Http\Livewire;

use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\ScClient;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ScAccountsList extends Component implements HasTable
{
    use InteractsWithTable;

    public ScCredentials $credentials;

    protected $listeners = [
        'scJwtStored' => 'updateAccounts'
    ];

    public function mount(): void
    {
        $this->credentials = auth()->user()->scCredentials;
    }

    public function render()
    {
        return view('livewire.sc-accounts-list');
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

    protected function getTableColumns(): array
    {
        return [
            IconColumn::make('is_primary')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->label('Primary')
                ->trueColor('success'),
            TextColumn::make('account_number')->label('Account Number'),
            TextColumn::make('description')->label('Description'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return auth()->user()->scAccounts()->getQuery();
    }
}
