<?php

namespace App\Livewire;

use App\Jobs\UpdateDailyReportsJob;
use App\Models\ScAccount;
use App\Models\ScDailyReport;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class DailyReportsTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
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

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ScDailyReport::whereIn(
                    'sc_account_id',
                    auth()->user()->scAccounts()->select('id')
                )
                    ->select()
                    ->addSelect(DB::raw('(coalesce(overage_high_kwh, 0) - coalesce(overage_low_kwh, 0)) as overage'))
                    ->join(
                        DB::raw('(SELECT id as account_id, account_number FROM sc_accounts) as acc'),
                        fn ($join) => $join->on('sc_daily_reports.sc_account_id', '=', 'acc.account_id')
                    )
                    ->orderBy('day_at', 'desc')
            )
            ->columns([
                TextColumn::make('account_number')->label('Account Number'),
                TextColumn::make('day_at')->label('Date')->date(),
                TextColumn::make('weekday_cost_usd')->label('Weekday Cost')->money('usd'),
                TextColumn::make('weekday_usage_kwh')->label('Weekday Usage')->formatStateUsing(
                    fn (float $state) => "{$state} kwh"
                ),
                TextColumn::make('weekend_cost_usd')->label('Weekend Cost')->money('usd'),
                TextColumn::make('weekend_usage_kwh')->label('Weekend Usage')->formatStateUsing(
                    fn (float $state) => "{$state} kwh"
                ),
                TextColumn::make('overage')->label('Overage')->formatStateUsing(
                    fn (float $state) => "{$state} kwh"
                ),
                TextColumn::make('temp_high_f')->label('High °F'),
                TextColumn::make('temp_low_f')->label('Low °F'),
        ]);
    }
}
