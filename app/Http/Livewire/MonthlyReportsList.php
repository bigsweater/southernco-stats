<?php

namespace App\Http\Livewire;

use App\Models\ScAccount;
use App\Models\ScMonthlyReport;
use App\ScClient;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MonthlyReportsList extends Component implements HasTable
{
    use InteractsWithTable;

    public function render()
    {
        return view('livewire.monthly-reports-list');
    }

    public function updateMonthlyReports()
    {
        $client = new ScClient(auth()->user()->scCredentials);

        auth()->user()->scAccounts->each(function (ScAccount $account) use ($client) {
            $monthly = $client->getMonthly($account);

            $converted = collect();

            $dates = Arr::get($monthly, 'xAxis.dates');
            $cost = Arr::get($monthly, 'series.cost.data');
            $usage = Arr::get($monthly, 'series.usage.data');
            $highTemp = Arr::get($monthly, 'series.highTemp.data');
            $lowTemp = Arr::get($monthly, 'series.lowTemp.data');

            foreach ($dates as $index => $date) {
                $converted->push(ScMonthlyReport::updateOrCreate([
                    'sc_account_id' => $account->getKey(),
                    'period_start_at' => new Carbon($date['startDate']),
                    'period_end_at' => new Carbon($date['endDate']),
                ], [
                    'cost_usd' => $cost[$index]['y'] ?? null,
                    'usage_kwh' => $usage[$index]['y'] ?? null,
                    'temp_high_f' => $highTemp[$index]['y'] ?? null,
                    'temp_low_f' => $lowTemp[$index]['y'] ?? null,
                ]));
            }
        });
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('account_number')->label('Account Number'),
            TextColumn::make('period_start_at')->label('Start')->date(),
            TextColumn::make('period_end_at')->label('End')->date(),
            TextColumn::make('cost_usd')->label('Cost')->formatStateUsing(
                fn (?string $state) => $state ? "\${$state}" : 'incomplete'
            ),
            TextColumn::make('usage_kwh')->label('Usage')->formatStateUsing(
                fn (?string $state) => $state ? "{$state} kwh" : 'incomplete'
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
        )->join(
            DB::raw('(SELECT id as account_id, account_number FROM sc_accounts) as acc'),
            fn ($join) => $join->on('sc_monthly_reports.sc_account_id', '=', 'acc.account_id')
        )->orderBy('period_end_at', 'desc');
    }
}
