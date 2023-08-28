<?php

namespace App\Livewire;

use App\Jobs\UpdateMonthlyReportsJob;
use App\Models\ScAccount;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Bus\Batch;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Livewire\Component;

class MonthlyReportsBackfillForm extends Component implements HasForms
{
    use InteractsWithForms;

    public ?int $accountId = null;
    public string $from;
    public ?string $batchId = null;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.monthly-reports-backfill-form');
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
            ? Bus::findBatch($this->batchId)
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
                ->default(auth()->user()->scAccounts->first()?->getKey())
                ->required(),
            DatePicker::make('from')
                ->default(now()->subYear())
                ->maxDate(now()->subYear())
                ->label('How far back to retrieve data?')
                ->required(),
        ];
    }
}
