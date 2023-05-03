<div>
    <form wire:submit.prevent="updateCredentials" wire:loading.class="opacity-50">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament-support::button type="submit">
                Update Credentials
            </x-filament-support::button>
        </div>
    </form>
</div>
