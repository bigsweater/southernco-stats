<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use App\Models\ScMonthlyReport;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Illuminate\Bus\BatchRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class MonthlyReportsList extends Component implements HasTable
{
    use InteractsWithTable;

    public ?string $batchId = null;

    public function render()
    {
        return view('livewire.monthly-reports-list');
    }

    public function isUpdating(): bool
    {
        return boolval($this->batchId)
            && boolval(($batch = app(BatchRepository::class)->find($this->batchId)))
            && ! $batch->finished();
    }

    public function updateMonthlyReports()
    {
        $batch = Bus::batch([]);

        auth()->user()->scAccounts->each(function (ScAccount $account) use ($batch) {
            $batch->add([new UpdateMonthlyReportsJob($account)]);
        });

        $this->batchId = $batch->dispatch()->id;
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

    protected function getTableFilters(): array
    {
        return [
            Filter::make('period')->form([
                DatePicker::make('period_start'),
                DatePicker::make('period_end'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query->when(
                    $data['period_start'],
                    fn (Builder $query, $date): Builder => $query->whereDate('period_start_at', '>=', $date)
                )
                ->when(
                    $data['period_end'],
                    fn (Builder $query, $date): Builder => $query->whereDate('period_end_at', '<=', $date)
                );

            })
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
