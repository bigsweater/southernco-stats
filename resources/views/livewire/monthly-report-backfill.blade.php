<div>
    <div class="h-12 flex items-baseline justify-between space-x-4 rtl:space-x-reverse">
        <h2 class="text-lg sm:text-xl font-bold tracking-tight">
            Download Past Data
        </h2>
    </div>

    <div>
        {{ $this->form }}

        <div class="pt-6">
            <x-filament-support::button wire:click.prevent="backfill">
                Submit
            </x-filament-support::button>
        </div>
    </div>
</div>
