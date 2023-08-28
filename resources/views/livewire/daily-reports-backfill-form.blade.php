<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
            Download Past Data
        </h2>
    </div>

    <form
        wire:submit.prevent="backfill"
        @if($this->isBackfilling)
        wire:poll
        disabled
        @endif
    >
        {{ $this->form }}

        <div class="pt-6 flex items-baseline space-x-4">
            <x-filament::button type="submit">
                Submit
            </x-filament::button>

            @if($this->isBackfilling)
            <span class="text-gray-600 text-sm">
            {{ $this->progress }}% complete
            </span>
            @endif

            @if($this->batch?->finished())
            <span class="text-gray-600 text-sm">
            Completed!
            </span>
            @endif

            @if($this->batch?->hasFailures())
            <span class="text-gray-600 text-sm">
            Some requests failed; you may need to try again.
            </span>
            @endif
        </div>
    </form>
</div>
