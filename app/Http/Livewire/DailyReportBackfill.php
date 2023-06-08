<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateDailyReportsJob;
use App\Models\ScAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Bus\Batch;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class DailyReportBackfill extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $accountId = null;
    public string $from;
    public ?string $batchId = null;

    public function render()
    {
        return view('livewire.daily-report-backfill');
    }

    public function backfill(): void
    {
        $this->form->getState();

        $batch = Bus::batch([]);

        $account = ScAccount::findOrFail($this->accountId);

        $from = new Carbon($this->from);
        $to = $from->copy()->addMonth();

        $this->batchJobsByMonth($account, $from, $to, $batch);

        $this->batchId = $batch->dispatch()->id;
    }

    public function getBatchProperty(): ?Batch
    {
        return $this->batchId
            ? Bus::findBatch($this->batchId)
            : null;
    }

    public function getIsBackfillingProperty(): bool
    {
        return $this->batch
            && !$this->batch?->finished()
            && !$this->batch?->canceled();
    }

    public function getProgressProperty(): ?int
    {
        return $this->batch?->progress();
    }

    private function batchJobsByMonth(ScAccount $account, Carbon $from, Carbon $to, PendingBatch &$batch): void
    {
        $now = now();

        if ($from->greaterThanOrEqualTo($now)) {
            return;
        }

        if ($to->greaterThanOrEqualTo($now)) {
            $to = $now;
        }

        if ($from->equalTo($to)) {
            return;
        }

        $batch->add(new UpdateDailyReportsJob(
            account: $account,
            startDate: $from,
            endDate: $to
        ));


        $this->batchJobsByMonth(
            account: $account,
            from: $from->copy()->addMonth(),
            to: $to->copy()->addMonth(),
            batch: $batch,
        );
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('accountId')
                ->options(auth()->user()->scAccounts()->pluck('description', 'id'))
                ->required(),
            DatePicker::make('from')->before(date: '1 month ago')->label('How far back to retrieve data?')->required(),
        ];
    }
}
