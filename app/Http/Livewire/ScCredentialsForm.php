<?php

namespace App\Http\Livewire;

use App\Models\ScCredentials;
use App\ScClient;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Illuminate\View\View;
use Livewire\Component;

class ScCredentialsForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ScCredentials $credentials;

    public function mount(): void
    {
        $this->form->fill([
            'username' => $this->credentials->username,
            'password' => $this->credentials->password,
        ]);
    }

    public function updateCredentials(): void
    {
        $state = $this->form->getState();
        $this->credentials->username = $state['username'];
        $this->credentials->password = $state['password'];
        $client = new ScClient($this->credentials);
        $this->credentials->jwt = $client->getJwt();
        $this->credentials->user()->associate(auth()->user());

        $this->credentials->save();
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('username')->required()->autocomplete('off'),
            TextInput::make('password')->password()->required()->autocomplete('off'),
        ];
    }

    protected function getFormModel(): ScCredentials
    {
        return $this->credentials;
    }

    public function render(): View
    {
        return view('livewire.sc-credentials-form');
    }
}
