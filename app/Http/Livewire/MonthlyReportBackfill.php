<?php

namespace App\Http\Livewire;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Livewire\Component;

class MonthlyReportBackfill extends Component implements HasForms
{
    use InteractsWithForms;

    public string $account;
    public string $from;
    public string $to;

    public function render()
    {
        return view('livewire.monthly-report-backfill');
    }

    public function backfill(): void
    {
        $state = $this->form->getState();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('account')
                ->options(auth()->user()->scAccounts()->pluck('description', 'account_number'))
                ->required(),
            DatePicker::make('from')->before('now')->required(),
            DatePicker::make('to')->after('from')->required(),
        ];
    }
}
