<?php

namespace App\Http\Livewire;

use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Bus\Batch;
use Illuminate\Bus\BatchRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class MonthlyReportBackfill extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $accountId = null;
    public string $from;
    public ?string $batchId = null;

    public function render()
    {
        return view('livewire.monthly-report-backfill');
    }

    public function backfill(): void
    {
        $this->form->getState();

        $batch = Bus::batch([]);

        $account = ScAccount::findOrFail($this->accountId);

        $from = new Carbon($this->from);
        $diff = $from->diffInYears(now());

        for ($i = 0; $i < $diff; $i++) {
            if ($i !== 0) {
                $from->addYear();
            }

            $batch->add(new UpdateMonthlyReportsJob(
                account: $account,
                startDate: $from->copy(),
                endDate: $from->copy()->addYear()
            ));
        }

        $this->batchId = $batch->dispatch()->id;
    }

    public function getBatchProperty(): ?Batch
    {
        return $this->batchId
            ? app(BatchRepository::class)->find($this->batchId)
            : null;
    }

    public function getIsBackfillingProperty(): bool
    {
        return $this->batch
            && !$this->batch->finished()
            && !$this->batch->canceled();
    }

    public function getProgressProperty(): ?int
    {
        if (!$this->batch) {
            return null;
        }

        return $this->batch->progress();
    }

    protected function getFormSchema(): array
    {
        return [
            Select::make('accountId')
                ->options(auth()->user()->scAccounts()->pluck('description', 'id'))
                ->required(),
            DatePicker::make('from')->before(date: '364 days ago')->label('How far back to retrieve data?')->required(),
        ];
    }
}
