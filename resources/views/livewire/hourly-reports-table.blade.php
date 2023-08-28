<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
             Hourly usage reports
        </h2>

        <form
            wire:submit.prevent="updateHourlyReports"
            @if($this->isUpdating())
            wire:poll
            disabled
            @endif
            class="disabled:pointer-events-none disabled:cursor-not-allowed"
        >
            <div class="flex items-baseline gap-4">
                @if($this->isUpdating())
                <p class="animate-pulse text-sm text-gray-300">Loading...</p>
                @endif

                <x-filament::button type="submit">
                    Refresh
                </x-filament::button>
            </div>
        </form>
    </div>

    {{ $this->table }}
</div>
