<div>
    <form wire:submit.prevent="updateCredentials" wire:loading.class="opacity-50">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament::button type="submit">
                Update Credentials
            </x-filament::button>
        </div>
    </form>
</div>
