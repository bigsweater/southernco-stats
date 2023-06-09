<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateHourlyReportsJob;
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

class HourlyReportsBackfillForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $accountId = null;
    public string $from;
    public ?string $batchId = null;

    public function backfill(): void
    {
        $this->form->getState();

        $batch = Bus::batch([]);

        $account = ScAccount::findOrFail($this->accountId);

        $from = new Carbon($this->from);
        $to = $from->copy()->addDay();

        $this->batchJobsByDay($account, $from, $to, $batch);

        $this->batchId = $batch->allowFailures()->dispatch()->id;
    }

    private function batchJobsByDay(ScAccount $account, Carbon $from, Carbon $to, PendingBatch &$batch): void
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

        $batch->add(new UpdateHourlyReportsJob(
            account: $account,
            startDate: $from,
            endDate: $to
        ));

        $this->batchJobsByDay(
            account: $account,
            from: $from->copy()->addDay(),
            to: $to->copy()->addDay(),
            batch: $batch,
        );
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
            && !$this->batch->finished()
            && !$this->batch->cancelled();
    }

    public function getProgressProperty(): ?int
    {
        return $this->batch?->progress();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('accountId')
                ->options(auth()->user()->scAccounts()->pluck('description', 'id'))
                ->required(),
            DatePicker::make('from')->before(date: '2 days ago')->label('How far back to retrieve data?')->required(),
        ];
    }

    public function render()
    {
        return view('livewire.hourly-reports-backfill-form');
    }
}
