<x-filament::widget>
    <x-filament::card>
        <div class="h-12 flex items-center space-x-4 rtl:space-x-reverse">
            <h2 class="text-lg sm:text-xl font-bold tracking-tight">
                Georgia Power Credentials
            </h2>
        </div>

        <livewire:sc-credentials-form :credentials="auth()->user()->scCredentials()->firstOrNew()" />
    </x-filament::card>
</x-filament::widget>
