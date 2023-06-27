<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateDailyReportsJob;
use App\Models\ScAccount;
use App\Models\ScDailyReport;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyReportsTable extends Component implements HasTable
{
    use InteractsWithTable;

    public ?string $batchId = null;

    public function render()
    {
        return view('livewire.daily-reports-table');
    }

    public function isUpdating(): bool
    {
        return boolval($this->batchId)
            && boolval($batch = Bus::findBatch($this->batchId))
            && !$batch->finished();
    }

    public function updateDailyReports()
    {
        $batch = Bus::batch([]);

        auth()->user()->scAccounts->each(function (ScAccount $account) use ($batch) {
            $batch->add([new UpdateDailyReportsJob($account)]);
        });

        $this->batchId = $batch->dispatch()->id;
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('account_number')->label('Account Number'),
            TextColumn::make('day_at')->label('Date')->date(),
            TextColumn::make('weekday_cost_usd')->label('Weekday Cost')->formatStateUsing(
                fn (?string $state) => $state ? "\${$state}" : ''
            ),
            TextColumn::make('weekday_usage_kwh')->label('Weekday Usage')->formatStateUsing(
                fn (?string $state) => $state ? "{$state} kwh" : ''
            ),
            TextColumn::make('weekend_cost_usd')->label('Weekend Cost')->formatStateUsing(
                fn (?string $state) => $state ? "\${$state}" : ''
            ),
            TextColumn::make('weekend_usage_kwh')->label('Weekend Usage')->formatStateUsing(
                fn (?string $state) => $state ? "{$state} kwh" : ''
            ),
            TextColumn::make('overage')->label('Overage')->formatStateUsing(
                fn (?string $state) => $state ? "{$state} kwh" : ''
            ),
            TextColumn::make('temp_high_f')->label('High °F'),
            TextColumn::make('temp_low_f')->label('Low °F'),
        ];
    }

    protected function getTableQuery(): Builder|Relation
    {
        return ScDailyReport::whereIn(
            'sc_account_id',
            auth()->user()->scAccounts()->select('id')
        )
            ->select()
            ->addSelect(DB::raw('(coalesce(overage_high_kwh, 0) - coalesce(overage_low_kwh, 0)) as overage'))
            ->join(
                DB::raw('(SELECT id as account_id, account_number FROM sc_accounts) as acc'),
                fn ($join) => $join->on('sc_daily_reports.sc_account_id', '=', 'acc.account_id')
            )
            ->orderBy('day_at', 'desc');
    }
}
