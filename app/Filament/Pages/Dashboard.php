<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ScCredentialsWidget;
use App\Filament\Widgets\ScAccountsWidget;
use \Filament\Pages\Dashboard as BasePage;
use Illuminate\Support\Collection;

class Dashboard extends BasePage
{
    protected static string $view = 'filament.pages.dashboard';

    protected function getScAccounts(): Collection
    {
        return auth()->user()->scAccounts()->select(
            'id', 'account_number', 'description'
        )->get();
    }

    public function getWidgets(): array
    {
        return [
            ScCredentialsWidget::class,
            ScAccountsWidget::class,
        ];
    }
}
