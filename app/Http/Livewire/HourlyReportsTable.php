<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateHourlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScHourlyReport;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class HourlyReportsTable extends Component implements HasTable
{
    use InteractsWithTable;

    public ?string $batchId = null;

    public function updateHourlyReports()
    {
        $batch = Bus::batch([]);

        auth()->user()->scAccounts->each(function (ScAccount $account) use ($batch) {
            $batch->add([new UpdateHourlyReportsJob($account)]);
        });

        $this->batchId = $batch->dispatch()->id;
    }

    public function isUpdating(): bool
    {
        return boolval($this->batchId)
            && boolval($batch = Bus::findBatch($this->batchId))
            && !$batch->finished();
    }

    protected function getTableColumns(): array
    {
        return [
            TextColumn::make('account_number')->label('Account Number'),
            TextColumn::make('hour_at')->label('Date')->dateTime('D M j, Y g:ia'),
            TextColumn::make('cost_usd')->label('Cost')->formatStateUsing(
                fn (?string $state) => $state ? '$'.number_format($state, 2) : ''
            ),
            TextColumn::make('usage_kwh')->label('Usage')->formatStateUsing(
                fn (?string $state) => $state ? "{$state} kwh" : ''
            ),
            TextColumn::make('temp_f')->label('Temp °F'),
            IconColumn::make('is_peak')
                ->label('On Peak?')
                ->boolean()
                ->trueIcon('heroicon-o-check-circle')
                ->trueColor('warning')
                ->falseIcon(''),
        ];
    }

    protected function getTableQuery(): Builder|Relation
    {
        return ScHourlyReport::whereIn(
            'sc_account_id',
            auth()->user()->scAccounts()->select('id')
        )
            ->join(
                DB::raw('(SELECT id as account_id, account_number FROM sc_accounts) as acc'),
                fn ($join) => $join->on('sc_hourly_reports.sc_account_id', '=', 'acc.account_id')
            )
            ->orderBy('hour_at', 'desc');
    }

    public function render()
    {
        return view('livewire.hourly-reports-table');
    }
}
