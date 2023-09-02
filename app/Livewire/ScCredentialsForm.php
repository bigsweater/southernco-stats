<?php

namespace App\Livewire;

use App\Models\ScCredentials;
use App\ScClient;
use DOMException;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ScCredentialsForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?string $username = null;

    public ?string $password = null;

    public function mount(): void
    {
        $this->form->fill([
            'username' => $this->credentials->username,
            'password' => $this->credentials->password,
        ]);
    }

    #[Computed]
    public function credentials(): ?ScCredentials
    {
        return ScCredentials::query()
            ->firstOrNew(['user_id' => auth()->user()->getKey() ]);
    }

    public function updateCredentials(): void
    {
        $state = $this->form->getState();

        $this->credentials->username = $state['username'];
        $this->credentials->password = $state['password'];

        $client = app(ScClient::class, [$this->credentials]);

        try {
            $this->credentials->jwt = $client->getJwt();
            $this->credentials->user()->associate(auth()->user());

            $this->credentials->save();
            $this->dispatch('scJwtStored');
        } catch (DOMException $e) {
            Log::info($e);
            throw ValidationException::withMessages([
                'username' => 'Something went wrong. Check your username and password, or try again later.'
            ]);
        }
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
