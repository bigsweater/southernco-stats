<?php

namespace App\Http\Livewire;

use App\Models\ScCredentials;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\View;
use Livewire\Component;

class ScCredentialsForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?ScCredentials $credentials;

    public function mount(): void
    {
        $this->form->fill([
            'username' => $this->credentials?->username,
            'password' => $this->credentials?->password,
        ]);
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('username')->required(),
            TextInput::make('password')->password()->required(),
        ];
    }

    protected function getFormModel(): ?Model
    {
        return $this->credentials;
    }

    public function render(): View
    {
        return view('livewire.sc-credentials-form');
    }
}
