<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
             Monthly usage reports
        </h2>

        <form
            wire:submit.prevent="updateMonthlyReports"
            @if($this->isUpdating())
            wire:poll
            disabled
            @endif
            class="disabled:pointer-events-none disabled:opacity-50"
        >
            <div class="flex items-baseline gap-4">
                @if($this->isUpdating())
                <p class="animate-pulse text-sm text-gray-300">Loading...</p>
                @endif

                <x-filament-support::button type="submit">
                    Refresh
                </x-filament-support::button>
            </div>
        </form>
    </div>

    {{ $this->table }}
</div>
