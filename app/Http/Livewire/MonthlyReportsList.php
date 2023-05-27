<?php

namespace App\Http\Livewire;

use App\Models\ScMonthlyReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class MonthlyReportsList extends Component implements HasTable
{
    use InteractsWithTable;

    public function render()
    {
        return view('livewire.monthly-reports-list');
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('account_number')->label('Account Number'),
            TextColumn::make('period_start_at')->label('Start')->date(),
            TextColumn::make('period_end_at')->label('End')->date(),
            TextColumn::make('cost_usd')->label('Cost')->formatStateUsing(
                fn (string $state) => "\${$state}"
            ),
            TextColumn::make('usage_kwh')->label('Usage')->formatStateUsing(
                fn (string $state) => "{$state} kwh"
            ),
            TextColumn::make('temp_high_f')->label('High °F'),
            TextColumn::make('temp_low_f')->label('Low °F'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return ScMonthlyReport::whereIn(
            'sc_account_id',
            auth()->user()->scAccounts()->select('id')
        )->join('sc_accounts', 'sc_monthly_reports.sc_account_id', '=', 'sc_accounts.id')
            ->orderBy('period_end_at', 'desc');
    }
}
