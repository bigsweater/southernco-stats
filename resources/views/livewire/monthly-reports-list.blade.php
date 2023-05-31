<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
             Monthly usage reports
        </h2>

        <div>
            <x-filament-support::button wire:click.prevent="updateMonthlyReports">
                Refresh
            </x-filament-support::button>
        </div>
    </div>

    {{ $this->table }}
</div>
