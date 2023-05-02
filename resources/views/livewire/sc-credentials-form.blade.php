<div>
    <form wire:submit.prevent="updateCredentials">
        {{ $this->form }}

        <div class="mt-6">
            <x-filament-support::button type="submit">
                Update Credentials
            </x-filament-support::button>
        </div>
    </form>
</div>
