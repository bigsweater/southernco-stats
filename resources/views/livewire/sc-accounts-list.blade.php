<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
            SouthernCo Accounts
        </h2>

        <div>
            <x-filament::button wire:click.prevent="updateAccounts">
                Refresh
            </x-filament::button>
        </div>
    </div>

    {{ $this->table }}
</div>
