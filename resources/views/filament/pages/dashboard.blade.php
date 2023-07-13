<x-filament::page class="filament-dashboard-page">
    @foreach($this->getScAccounts() as $account)
    <div>
        <h2 class="font-bold tracking-tight text-lg">Account {{ $account->account_number }}</h2>
        <p class="text-sm uppercase text-gray-600">{{ $account->description }}</p>
    </div>
    <x-filament::widgets
        :widgets="[ \App\Filament\Widgets\CurrentStatsWidget::class ]"
        :columns="$this->getColumns()"
        :data="[ 'scAccountId' => $account->id ]"
    />
    @endforeach

    <x-filament::widgets
        :widgets="$this->getWidgets()"
        :columns="$this->getColumns()"
    />
</x-filament::page>
